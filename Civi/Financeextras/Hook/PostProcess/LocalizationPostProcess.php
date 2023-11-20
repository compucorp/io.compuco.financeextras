<?php

namespace Civi\Financeextras\Hook\PostProcess;

class LocalizationPostProcess {

  private $form;

  public function __construct($form) {
    $this->form = $form;
  }

  public function handle() {
    $this->updateSalexTaxCurrencyField();
  }

  public function updateSalexTaxCurrencyField() {
    \Civi\Api4\CustomField::update(FALSE)
      ->addValue('option_group_id.name', 'currencies_enabled')
      ->addWhere('name', '=', 'sales_tax_currency')
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
    return $formName === "CRM_Admin_Form_Setting_Localization";
  }

}
