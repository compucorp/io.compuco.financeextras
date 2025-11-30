<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

use Civi\WorkflowMessage\WorkflowMessage;
use CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation as ContributionOwnerOrganisation;
use Civi\Financeextras\Common\GCManager;

/**
 * Provides separate invoicing template and tokens for each
 * company (legal entity) and adds tax conversion table data.
 */
class InvoiceTemplate {

  private $templateParams;

  private $contributionId;

  private $contributionOwnerCompany;

  private static $processedInvoices = 0;
  private static $contributionCache = [];
  private static $contributionCacheOrder = [];
  private static $ownerCompanyCache = [];
  private static $ownerCompanyCacheOrder = [];
  private static $locationCache = [];
  private static $locationCacheOrder = [];
  private static $maxCacheSize = 100;

  public function __construct(&$templateParams, $context) {
    $this->templateParams = &$templateParams;
    $this->contributionId = $templateParams['tplParams']['id'];
  }

  public function handle() {
    self::$processedInvoices++;

    try {
      $this->addTaxConversionTable();

      // Get owner company from cache or fetch
      $this->contributionOwnerCompany = $this->getOwnerCompanyFromCache($this->contributionId);
      if (!$this->contributionOwnerCompany) {
        $this->contributionOwnerCompany = ContributionOwnerOrganisation::getOwnerOrganisationCompany($this->contributionId);
        // Cache using LRU
        $this->addToLRUCache(self::$ownerCompanyCache, self::$ownerCompanyCacheOrder, $this->contributionId, $this->contributionOwnerCompany);
      }

      if (empty($this->contributionOwnerCompany)) {
        return;
      }

      $this->useContributionOwnerOrganisationInvoiceTemplate();
      $this->replaceDomainTokensWithOwnerOrganisationTokens();

      // Adaptive memory management: Batch-complete trigger after each invoice
      // Uses conservative approach with memory-threshold backup
      GCManager::maybeCollectGarbage('invoice_processing');
    } catch (Exception $e) {
      // Log error and continue processing other invoices
      \Civi::log()->error('InvoiceTemplate processing failed for contribution ' . $this->contributionId . ': ' . $e->getMessage());
      throw $e;
    }
  }

  private function addTaxConversionTable() {
    $showTaxConversionTable = TRUE;

    // Check LRU cache first
    $contribution = $this->getContributionFromCache($this->contributionId);
    if (!$contribution) {
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

      // Cache the result using LRU
      $this->addToLRUCache(self::$contributionCache, self::$contributionCacheOrder, $this->contributionId, $contribution);
    }
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

    // Check LRU cache first
    $locationDefaults = $this->getLocationFromCache($ownerOrganisationId);
    if (!$locationDefaults) {
      $locationDefaults = \CRM_Core_BAO_Location::getValues(['contact_id' => $ownerOrganisationId]);
      // Cache using LRU
      $this->addToLRUCache(self::$locationCache, self::$locationCacheOrder, $ownerOrganisationId, $locationDefaults);
    }

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

  /**
   * Gets contribution data from LRU cache.
   */
  private function getContributionFromCache($contributionId) {
    if (isset(self::$contributionCache[$contributionId])) {
      $this->updateLRUOrder(self::$contributionCacheOrder, $contributionId);
      return self::$contributionCache[$contributionId];
    }
    return FALSE;
  }

  /**
   * Gets owner company data from LRU cache.
   */
  private function getOwnerCompanyFromCache($contributionId) {
    if (isset(self::$ownerCompanyCache[$contributionId])) {
      $this->updateLRUOrder(self::$ownerCompanyCacheOrder, $contributionId);
      return self::$ownerCompanyCache[$contributionId];
    }
    return FALSE;
  }

  /**
   * Gets location data from LRU cache.
   */
  private function getLocationFromCache($contactId) {
    if (isset(self::$locationCache[$contactId])) {
      $this->updateLRUOrder(self::$locationCacheOrder, $contactId);
      return self::$locationCache[$contactId];
    }
    return FALSE;
  }

  /**
   * Updates LRU order by moving item to end (most recently used).
   */
  private function updateLRUOrder(&$orderArray, $key) {
    $index = array_search($key, $orderArray);
    if ($index !== FALSE) {
      unset($orderArray[$index]);
      $orderArray = array_values($orderArray); // Re-index array
    }
    $orderArray[] = $key;
  }

  /**
   * Adds item to LRU cache, evicting least recently used if at capacity.
   */
  private function addToLRUCache(&$cache, &$orderArray, $key, $value) {
    // If already exists, update value and move to end
    if (isset($cache[$key])) {
      $cache[$key] = $value;
      $this->updateLRUOrder($orderArray, $key);
      return;
    }

    // If at capacity, remove least recently used item
    if (count($cache) >= self::$maxCacheSize) {
      $lruKey = array_shift($orderArray);
      unset($cache[$lruKey]);
    }

    // Add new item
    $cache[$key] = $value;
    $orderArray[] = $key;
  }

}
