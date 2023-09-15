<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use Civi\Financeextras\Hook\ValidateForm\OwnerOrganizationRetriever as OwnerOrganizationRetriever;

/**
 * Form Validation on line item edit form, that is provided by
 * Lineitemedit extension.
 */
class LineItemEdit {

  private $form;

  private $fields;

  private $errors;

  private $formName;

  public function __construct($form, &$fields, &$errors, $formName) {
    $this->form = $form;
    $this->fields = &$fields;
    $this->errors = &$errors;
    $this->formName = $formName;
  }

  public function handle() {
    $this->validateConsistentIncomeAccountOwners();
  }

  /**
   * Validates if the financial type owner
   * for the line item being edited matches
   * the original line item financial type owner.
   *
   * @return void
   */
  private function validateConsistentIncomeAccountOwners() {
    $lineItemId = $this->form->_id;
    $currentFinancialTypeId = civicrm_api3('LineItem', 'getvalue', [
      'return' => 'financial_type_id',
      'id' => $lineItemId,
    ]);

    $selectedFinancialTypeId = $this->fields['financial_type_id'];
    $financialTypeOwners = OwnerOrganizationRetriever::getFinancialTypesOwnerOrganizationIds([$currentFinancialTypeId, $selectedFinancialTypeId]);
    if (count($financialTypeOwners) > 1) {
      $this->errors['financial_type_id'] = 'It is not possible to make the proposed changes to this line item as the owner organisation of the contribution is not connected to the financial type of the proposed line item. Please update the financial type.';
    }
  }

  public static function shouldHandle($form, $formName) {
    return $formName === 'CRM_Lineitemedit_Form_Edit';
  }

}
