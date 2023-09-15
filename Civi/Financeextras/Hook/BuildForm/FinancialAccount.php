<?php

namespace Civi\Financeextras\Hook\BuildForm;

class FinancialAccount {

  private $form;

  public function __construct($form) {
    $this->form = $form;
  }

  public function handle() {
    $this->restrictOwnerFieldToCompanyOrgnisations();
  }

  private function restrictOwnerFieldToCompanyOrgnisations() {
    $element = $this->form->getElement('contact_id');
    $element->setAttribute('data-api-entity', 'Company');
    $element->setAttribute('data-api-params', json_encode([
      'search_field' => 'contact_id.organization_name',
      'label_field' => 'contact_id.organization_name',
      'id_field' => 'contact_id',
    ]));
    $element->setAttribute('data-select-params', json_encode(['minimumInputLength' => 0]));
  }

  public static function shouldHandle($form, $formName) {
    return $formName === "CRM_Financial_Form_FinancialAccount";
  }

}
