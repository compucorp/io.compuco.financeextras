<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use Civi\Financeextras\Hook\ValidateForm\OwnerOrganizationRetriever as OwnerOrganizationRetriever;

/**
 * Price Set Form Validation
 */
class PriceSetValidator {

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
   * account for the selected financial type, matches
   * the owner of the income account for the
   * price fields under the price set.
   *
   * @return void
   */
  private function validateConsistentIncomeAccountOwner() {
    $selectedPriceSetFinancialTypeOwnerOrganization = $this->getSelectedFinancialTypeOwnerOrganization();
    if (empty($selectedPriceSetFinancialTypeOwnerOrganization)) {
      return;
    }

    $priceSetFirstPriceFieldOwnerOrganization = $this->getPriceSetOwnerOrganizationForTheFirstChildPriceField();
    if (empty($priceSetFirstPriceFieldOwnerOrganization)) {
      return;
    }

    if ($selectedPriceSetFinancialTypeOwnerOrganization[0] != $priceSetFirstPriceFieldOwnerOrganization[0]) {
      $this->errors['financial_type_id'] = 'The owner of the income account for the selected financial type does not match the owner of the income account for the financial types of the child price fields.';
    }
  }

  private function getSelectedFinancialTypeOwnerOrganization() {
    $selectedFinancialTypeId = $this->fields['financial_type_id'];
    return OwnerOrganizationRetriever::getFinancialTypesOwnerOrganizationIds([$selectedFinancialTypeId]);
  }

  /**
   * We get only the financial type for the
   * first child price field, because we have validation
   * on the price fields as well, so it is guaranteed
   * they all have the same owner organization.
   *
   * @return array|null
   */
  private function getPriceSetOwnerOrganizationForTheFirstChildPriceField() {
    $priceSetId = $this->form->getEntityId();
    $query = "SELECT pv.financial_type_id FROM civicrm_price_set ps
              INNER JOIN civicrm_price_field pf ON ps.id = pf.price_set_id
              INNER JOIN civicrm_price_field_value pv ON pf.id = pv.price_field_id
              WHERE ps.id = {$priceSetId} LIMIT 1";
    $firstChildPriceFieldFinancialTypeId = \CRM_Core_DAO::singleValueQuery($query);
    if (!$firstChildPriceFieldFinancialTypeId) {
      return NULL;
    }

    return OwnerOrganizationRetriever::getFinancialTypesOwnerOrganizationIds([$firstChildPriceFieldFinancialTypeId]);
  }

  public static function shouldHandle($form, $formName) {
    if ($formName == 'CRM_Price_Form_Set' && ($form->getAction() & \CRM_Core_Action::UPDATE)) {
      return TRUE;
    }

    return FALSE;
  }

}
