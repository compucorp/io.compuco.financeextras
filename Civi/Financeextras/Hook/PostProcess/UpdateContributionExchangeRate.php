<?php

namespace Civi\Financeextras\Hook\PostProcess;

class UpdateContributionExchangeRate {

  /**
   * @param \CRM_Contribute_Form_Contribution $form
   */
  public function __construct(private \CRM_Contribute_Form_Contribution $form) {
  }

  public function handle() {
    $this->updateSalesTaxCurrency();
  }

  private function updateSalesTaxCurrency() {
    $salesTaxCurrency = \Civi::settings()->get('fe_exchange_rate_settings')['sales_tax_currency'] ?? NULL;

    if (empty($salesTaxCurrency)) {
      return;
    }

    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $this->form->_id)
      ->setLimit(1)
      ->execute()
      ->first();

    if ($contribution['currency'] === $salesTaxCurrency) {
      return;
    }

    $exchangeRate = \Civi\Api4\ExchangeRate::get(FALSE)
      ->addOrderBy('exchange_date', 'ASC')
      ->addWhere('base_currency', '=', $salesTaxCurrency)
      ->addWhere('exchange_date', '<', $contribution['receive_date'])
      ->addWhere('conversion_currency', '=', $contribution['currency'])
      ->setLimit(1)
      ->execute()
      ->first();

    if (empty($exchangeRate)) {
      return;
    }

    \Civi\Api4\Contribution::update(FALSE)
      ->addWhere('id', '=', $this->form->_id)
      ->addValue('Currency_Exchange_rates.sales_tax_currency', $salesTaxCurrency)
      ->addValue('Currency_Exchange_rates.rate_1_unit_tax_currency', $exchangeRate['base_to_conversion_rate'])
      ->addValue('Currency_Exchange_rates.rate_1_unit_contribution_currency', $exchangeRate['conversion_to_base_rate'])
      ->execute();
  }

  /**
   * Checks if the hook should run.
   *
   * @param CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName) {
    $addOrUpdate = ($form->getAction() & \CRM_Core_Action::ADD) || ($form->getAction() & \CRM_Core_Action::UPDATE);
    return $formName === "CRM_Contribute_Form_Contribution" && $addOrUpdate;
  }

}
