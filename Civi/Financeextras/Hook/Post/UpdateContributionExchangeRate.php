<?php

namespace Civi\Financeextras\Hook\Post;

class UpdateContributionExchangeRate {

  /**
   * @param int $contributionId
   */
  public function __construct(private $contributionId) {
  }

  public function run() {
    $this->updateSalesTaxCurrency();
  }

  private function updateSalesTaxCurrency() {
    $salesTaxCurrency = \Civi::settings()->get('fe_exchange_rate_settings')['sales_tax_currency'] ?? NULL;

    if (empty($salesTaxCurrency)) {
      return;
    }

    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->setLimit(1)
      ->execute()
      ->first();

    if ($contribution['currency'] === $salesTaxCurrency) {
      return;
    }

    $exchangeRate = \Civi\Api4\ExchangeRate::get(FALSE)
      ->addOrderBy('exchange_date', 'DESC')
      ->addWhere('base_currency', '=', $salesTaxCurrency)
      ->addWhere('exchange_date', '<', $contribution['receive_date'])
      ->addWhere('conversion_currency', '=', $contribution['currency'])
      ->setLimit(1)
      ->execute()
      ->first();

    if (empty($exchangeRate)) {
      return;
    }

    $customFields = \Civi\Api4\CustomField::get(FALSE)
      ->addSelect('id', 'name')
      ->addWhere('custom_group_id:name', '=', 'financeextras_currency_exchange_rates')
      ->execute();

    $values = [
      'entity_id' => $this->contributionId,
    ];

    foreach ($customFields as $customField) {
      if ($customField['name'] == 'sales_tax_currency') {
        $values["custom_" . $customField['id']] = $salesTaxCurrency;
      }

      if ($customField['name'] == 'rate_1_unit_tax_currency') {
        $values["custom_" . $customField['id']] = $exchangeRate['base_to_conversion_rate'];
      }

      if ($customField['name'] == 'rate_1_unit_contribution_currency') {
        $values["custom_" . $customField['id']] = $exchangeRate['conversion_to_base_rate'];
      }
    }

    \CRM_Core_BAO_CustomValueTable::setValues($values);
  }

}
