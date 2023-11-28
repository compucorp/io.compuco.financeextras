<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use Civi\Financeextras\Hook\ValidateForm\OwnerOrganizationRetriever as OwnerOrganizationRetriever;

/**
 * Owner Organization Form Validation
 */
class OwnerOrganizationValidator {

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
    $this->validateConsistentIncomeAccountOwner();
  }

  /**
   * Validates if the owner organization of the income
   * account for the selected financial type(s), match
   * the owner of the income account for the
   * price set financial type.
   *
   * @return void
   */
  private function validateConsistentIncomeAccountOwner() {
    if ($this->formName == 'CRM_Event_Form_ManageEvent_Fee') {
      if (empty($this->fields['price_set_id'])) {
        return;
      }
    }

    $selectedFinancialTypesOwnerOrganizations = $this->getSelectedFinancialTypesOwnerOrganizations();
    if (empty($selectedFinancialTypesOwnerOrganizations)) {
      return;
    }

    if (count($selectedFinancialTypesOwnerOrganizations) > 1) {
      $this->errors['financial_type_id'] = 'The owner of the income account for the financial types you selected do not match';
      return;
    }

    $priceSetOwnerOrganization = $this->getPriceSetOwnerOrganization();
    if ($selectedFinancialTypesOwnerOrganizations[0] != $priceSetOwnerOrganization) {
      $this->errors['financial_type_id'] = 'The owner of the income account for the financial type you selected, does not match the owner of the income account for price set financial type.';
    }
  }

  private function getSelectedFinancialTypesOwnerOrganizations() {
    $selectedFinancialTypes = $this->getSelectedFinancialTypes();
    if (empty($selectedFinancialTypes)) {
      return [];
    }

    return OwnerOrganizationRetriever::getFinancialTypesOwnerOrganizationIds($selectedFinancialTypes);
  }

  /**
   * Gets the financial types list
   * that are selected by the user
   * on the form.
   *
   * @return array
   */
  private function getSelectedFinancialTypes() {
    $selectedFinancialTypes = [];
    if (!empty($this->fields['html_type']) && $this->fields['html_type'] != 'Text') {
      foreach ($this->fields['option_label'] as $index => $optionLabel) {
        if (!empty($optionLabel)) {
          $selectedFinancialTypes[] = $this->fields['option_financial_type_id'][$index];
        }
      }
    }
    else {
      $selectedFinancialTypes = [$this->fields['financial_type_id']];
    }

    return $selectedFinancialTypes;
  }

  /**
   * Gets the owner organization
   * for the income account associated
   * with financial type of the parent
   * price set of this price field.
   *
   * @return string|null
   */
  private function getPriceSetOwnerOrganization() {
    if (in_array($this->formName, ['CRM_Price_Form_Field', 'CRM_Price_Form_Option'])) {
      $priceSetId = \CRM_Utils_Request::retrieve('sid', 'Positive');
    }

    if ($this->formName == 'CRM_Event_Form_ManageEvent_Fee') {
      $priceSetId = $this->fields['price_set_id'];
    }

    $priceSetFinancialTypeId = civicrm_api3('PriceSet', 'getvalue', [
      'return' => 'financial_type_id',
      'id' => $priceSetId,
    ]);

    return OwnerOrganizationRetriever::getFinancialTypesOwnerOrganizationIds([$priceSetFinancialTypeId])[0];
  }

  public static function shouldHandle($form, $formName) {
    if (in_array($formName, ['CRM_Price_Form_Field', 'CRM_Price_Form_Option', 'CRM_Event_Form_ManageEvent_Fee'])) {
      return TRUE;
    }

    return FALSE;
  }

}
