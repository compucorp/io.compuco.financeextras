<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

use Civi\WorkflowMessage\WorkflowMessage;
use CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation as ContributionOwnerOrganisation;

/**
 * Provides separate invoicing template and tokens for each
 * company (legal entity) and adds tax conversion table data.
 */
class InvoiceTemplate {

  private $templateParams;

  private $contributionId;

  private $contributionOwnerCompany;

  public function __construct(&$templateParams, $context) {
    $this->templateParams = &$templateParams;
    $this->contributionId = $templateParams['tplParams']['id'];
  }

  public function handle() {
    $this->addTaxConversionTable();

    $this->contributionOwnerCompany = ContributionOwnerOrganisation::getOwnerOrganisationCompany($this->contributionId);
    if (empty($this->contributionOwnerCompany)) {
      return;
    }

    $this->useContributionOwnerOrganisationInvoiceTemplate();
    $this->replaceDomainTokensWithOwnerOrganisationTokens();
  }

  private function addTaxConversionTable() {
    $showTaxConversionTable = TRUE;
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect(
        'financeextras_currency_exchange_rates.rate_1_unit_tax_currency',
        'financeextras_currency_exchange_rates.rate_1_unit_contribution_currency',
        'financeextras_currency_exchange_rates.sales_tax_currency',
        'financeextras_currency_exchange_rates.vat_text'
      )->setLimit(1)
      ->addWhere('id', '=', $this->contributionId)
      ->execute()
      ->first();
    if (empty($contribution['financeextras_currency_exchange_rates.rate_1_unit_tax_currency'])) {
      $showTaxConversionTable = FALSE;
    }

    $this->templateParams['tplParams'] = array_merge($this->templateParams['tplParams'], [
      'showTaxConversionTable' => $showTaxConversionTable,
      'rate_1_unit_tax_currency' => $contribution['financeextras_currency_exchange_rates.rate_1_unit_tax_currency'] ?? "",
      'rate_1_unit_contribution_currency' => $contribution['financeextras_currency_exchange_rates.rate_1_unit_contribution_currency'] ?? "",
      'sales_tax_currency' => $contribution['financeextras_currency_exchange_rates.sales_tax_currency'] ?? "",
      'rate_vat_text' => $contribution['financeextras_currency_exchange_rates.vat_text'] ?? "",
    ]);
  }

  /**
   * Determines if the hook will run.
   *
   * @param array $params
   *   Mail parameters.
   * @param string $context
   *   Mail context.
   *
   * @return bool
   *   returns TRUE if hook should run, FALSE otherwise.
   */
  public static function shouldHandle($params, $context) {
    // 'contribution_invoice_receipt' is CiviCRM standard invoice template
    if (empty($params['valueName']) || $params['valueName'] != 'contribution_invoice_receipt') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Replaces the default civicrm invoice template
   * by the one configured on the contribution owner
   * organisation.
   *
   * @return void
   */
  private function useContributionOwnerOrganisationInvoiceTemplate() {
    $this->templateParams['messageTemplateID'] = $this->contributionOwnerCompany['invoice_template_id'];
    /** @var \Civi\WorkflowMessage\GenericWorkflowMessage $model */
    $model = $this->templateParams['model'] ?? WorkflowMessage::create($this->templateParams['workflow'] ?? 'UNKNOWN');
    WorkflowMessage::importAll($model, $this->templateParams);
    $mailContent = $model->resolveContent();
    \Civi::cache('session')->set('fe_org_message_template', base64_encode(json_encode($mailContent)));
  }

  /**
   * Replaces the standard domain tokens in the
   * invoice template, so they use the information
   * from the contribution owner organisation instead
   * of getting it from the domain record.
   *
   * @return void
   */
  private function replaceDomainTokensWithOwnerOrganisationTokens() {
    $ownerOrganisationLocation = $this->getOwnerOrganisationLocation();

    $replacementParams = [
      'domain_organization' => $this->contributionOwnerCompany['name'],
      'domain_logo' => \CRM_Utils_Array::value('logo_url', $this->contributionOwnerCompany, ''),
      'domain_street_address' => \CRM_Utils_Array::value('street_address', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['address'])),
      'domain_supplemental_address_1' => \CRM_Utils_Array::value('supplemental_address_1', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['address'])),
      'domain_supplemental_address_2' => \CRM_Utils_Array::value('supplemental_address_2', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['address'])),
      'domain_supplemental_address_3' => \CRM_Utils_Array::value('supplemental_address_3', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['address'])),
      'domain_city' => \CRM_Utils_Array::value('city', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['address'])),
      'domain_postal_code' => \CRM_Utils_Array::value('postal_code', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['address'])),
      'domain_state' => $ownerOrganisationLocation['address'][1]['state_province_abbreviation'],
      'domain_country' => $ownerOrganisationLocation['address'][1]['country'],
      'domain_email' => \CRM_Utils_Array::value('email', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['email'])),
      'domain_phone' => \CRM_Utils_Array::value('phone', \CRM_Utils_Array::value('1', $ownerOrganisationLocation['phone'])),
    ];

    $this->templateParams['tplParams'] = array_merge($this->templateParams['tplParams'], $replacementParams);
  }

  /**
   * Gets the owner organisation location details.
   *
   * This method as well as `replaceDomainTokensWithOwnerOrganisationTokens`
   * are to some degree copied from CiviCRM core
   * to make sure the experience is kinda similar between sites
   * that have this extension enabled and the sites that don't:
   * https://github.com/compucorp/civicrm-core/blob/5.39.1/CRM/Contribute/Form/Task/Invoice.php#L342-L356
   *
   * @return array
   */
  private function getOwnerOrganisationLocation() {
    $ownerOrganisationId = $this->contributionOwnerCompany['contact_id'];
    $locationDefaults = \CRM_Core_BAO_Location::getValues(['contact_id' => $ownerOrganisationId]);

    if (!empty($locationDefaults['address'][1]['state_province_id'])) {
      $locationDefaults['address'][1]['state_province_abbreviation'] = \CRM_Core_PseudoConstant::stateProvinceAbbreviation($locationDefaults['address'][1]['state_province_id']);
    }
    else {
      $locationDefaults['address'][1]['state_province_abbreviation'] = '';
    }

    if (!empty($locationDefaults['address'][1]['country_id'])) {
      $locationDefaults['address'][1]['country'] = \CRM_Core_PseudoConstant::country($locationDefaults['address'][1]['country_id']);
    }
    else {
      $locationDefaults['address'][1]['country'] = '';
    }

    return $locationDefaults;
  }

}
