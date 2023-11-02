<?php

namespace Civi\Financeextras\Hook\ValidateForm;

/**
 * Form Validation on editing or assigning a financial account for a financial type
 */
class FinancialTypeAccount {

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
    $this->validateFinancialAccountOrganisation();
  }

  /**
   * Validates the financial type account form
   * when editing or assigning a new account
   */
  private function validateFinancialAccountOrganisation() {
    $isAddAction = $this->form->getAction() === \CRM_Core_Action::ADD;
    $isUpdateAction = $this->form->getAction() === \CRM_Core_Action::UPDATE;
    $isFormActionToValidate = $isAddAction || $isUpdateAction;
    if (!$isFormActionToValidate) {
      return;
    }

    $financialTypeId = $this->form->getVar('_aid');
    $existingFinancialAccountsData = $this->getExistingFinancialTypeAccountsCountAndOwner($financialTypeId);
    $selectedFinancialAccountOwnerOrganisationId = $this->getSelectedFinancialAccountOwnerOrganisationId();

    // Allow changing the owner organisation if there is only one financial account.
    if ($isUpdateAction && $existingFinancialAccountsData['accounts_count'] === 1) {
      return;
    }
    // Allow adding a financial account type if there are no financial accounts assigned
    if ($isAddAction && $existingFinancialAccountsData['accounts_count'] === 0) {
      return;
    }

    if ($selectedFinancialAccountOwnerOrganisationId !== $existingFinancialAccountsData['owner_organisation_id']) {
      $this->errors['financial_account_id'] = ts('You cannot have multiple Owners for a Financial Type');
    }
  }

  /**
   * Gets the Financial Accounts based on financial type id sent by the form
   */
  private function getExistingFinancialTypeAccountsCountAndOwner($financialTypeId) {
    $result = \Civi\Api4\EntityFinancialAccount::get()
      ->addSelect('COUNT(*) AS count', 'financial_account.contact_id')
      ->setJoin([['FinancialAccount AS financial_account', 'INNER', NULL, ['financial_account_id', '=', 'financial_account.id']]])
      ->setGroupBy(['financial_account.contact_id'])
      ->addWhere('entity_table', '=', 'civicrm_financial_type')
      ->addWhere('entity_id', '=', $financialTypeId)
      ->execute()
      ->first();

    return [
      'accounts_count' => $result['count'] ?? 0,
      'owner_organisation_id' => $result['financial_account.contact_id'] ?? 0,
    ];
  }

  /**
   * Gets the owner of the financial account from the submitted fields
   */
  private function getSelectedFinancialAccountOwnerOrganisationId() {
    $result = \Civi\Api4\FinancialAccount::get()
      ->addSelect('contact_id')
      ->addWhere('id', '=', $this->fields['financial_account_id'])
      ->execute()
      ->first();

    return $result['contact_id'];
  }

  public static function shouldHandle($form, $formName) {
    return $formName === 'CRM_Financial_Form_FinancialTypeAccount';
  }

}
