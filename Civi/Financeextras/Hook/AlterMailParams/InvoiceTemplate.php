<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

use CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation as ContributionOwnerOrganisation;

/**
 * Provides separate invoicing template and tokens for each
 * company (legal entity).
 */
class InvoiceTemplate {

  private $templateParams;

  private $contributionId;

  private $contributionOwnerCompany;

  public function __construct(&$templateParams) {
    $this->templateParams = &$templateParams;
    $this->contributionId = $templateParams['tplParams']['id'];
  }

  public function run() {
    $this->contributionOwnerCompany = ContributionOwnerOrganisation::getOwnerOrganisationCompany($this->contributionId);
    if (empty($this->contributionOwnerCompany)) {
      return;
    }

    $this->useContributionOwnerOrganisationInvoiceTemplate();
    $this->replaceDomainTokensWithOwnerOrganisationTokens();
  }

  /**
   * Replaces the default civicrm invoice template
   * by the one configured on the contribution owner
   * organisation.
   * This is possible because CiviCRM calls this hook
   * before loading the template:
   * https://github.com/civicrm/civicrm-core/blob/5.39.1/CRM/Core/BAO/MessageTemplate.php#L418
   *
   * @return void
   */
  private function useContributionOwnerOrganisationInvoiceTemplate() {
    $this->templateParams['messageTemplateID'] = $this->contributionOwnerCompany['invoice_template_id'];
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
