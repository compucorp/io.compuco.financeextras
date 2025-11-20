<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use BaseHeadlessTest;

/**
 * @group headless
 */
class FinancialTypeAccountTest extends BaseHeadlessTest {

  private $form;

  private $financialTypeId;

  private $donationFinancialAccountId;

  private $accountsReceivableFinancialAccountId;

  public function setUp(): void {
    parent::setUp();

    $this->form = new \CRM_Financial_Form_FinancialTypeAccount();

    $this->financialTypeId = civicrm_api3('FinancialType', 'create', [
      'sequential' => 1,
      'name' => "Test",
    ])['id'];

    $this->donationFinancialAccountId = civicrm_api3('FinancialAccount', 'getvalue', [
      'return' => "id",
      'name' => "Donation",
    ]);

    $this->accountsReceivableFinancialAccountId = civicrm_api3('FinancialAccount', 'getvalue', [
      'return' => "id",
      'name' => "Accounts Receivable",
    ]);

    $firstOwnerOrgId = $this->createCompany(1)['contact_id'];
    $this->updateFinancialAccountOwner('Donation', $firstOwnerOrgId);

    $this->updateFinancialAccountOwner('Accounts Receivable', $firstOwnerOrgId);
    $this->updateFinancialAccountOwner('Banking Fees', $firstOwnerOrgId);
    $this->updateFinancialAccountOwner('Premiums', $firstOwnerOrgId);
    $this->updateFinancialAccountOwner(strtolower("Test"), $firstOwnerOrgId);
  }

  public function testUpdateFinancialTypeAccountWithFinancialAccountOwnerMatchesTheAssignedOwnerWillPassValidation() {
    $errors = [];
    $fields = [];
    $this->form->setAction(\CRM_Core_Action::UPDATE);
    $this->form->setVar('_aid', $this->financialTypeId);
    $fields['financial_account_id'] = $this->donationFinancialAccountId;

    $hook = new \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount($this->form, $fields, $errors, 'CRM_Financial_Form_FinancialTypeAccount');
    $hook->handle();
    $this->assertEmpty($errors);
  }

  public function testUpdateFinancialTypeAccountWithFinancialAccountOwnerNotMatchesTheAssignedOwnerWillFailValidation() {
    $errors = [];
    $fields = [];
    $this->form->setAction(\CRM_Core_Action::UPDATE);
    $this->form->setVar('_aid', $this->financialTypeId);
    $secondOwnerOrgId = $this->createCompany(2)['contact_id'];
    $this->updateFinancialAccountOwner('Donation', $secondOwnerOrgId);
    $fields['financial_account_id'] = $this->donationFinancialAccountId;

    $hook = new \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount($this->form, $fields, $errors, 'CRM_Financial_Form_FinancialTypeAccount');
    $hook->handle();
    $this->assertNotEmpty($errors['financial_account_id']);
  }

  public function testAddFinancialTypeAccountWithFinancialAccountOwnerMatchesTheAssignedOwnerWillPassValidation() {
    $errors = [];
    $fields = [];
    $this->form->setAction(\CRM_Core_Action::ADD);
    $this->form->setVar('_aid', $this->financialTypeId);

    $fields['financial_account_id'] = $this->donationFinancialAccountId;

    $hook = new \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount($this->form, $fields, $errors, 'CRM_Financial_Form_FinancialTypeAccount');
    $hook->handle();
    $this->assertEmpty($errors);
  }

  public function testAddFinancialTypeAccountWithFinancialAccountOwnerNotMatchesTheAssignedOwnerWillFailValidation() {
    $errors = [];
    $fields = [];
    $this->form->setAction(\CRM_Core_Action::ADD);
    $this->form->setVar('_aid', $this->financialTypeId);

    $secondOwnerOrgId = $this->createCompany(2)['contact_id'];
    $this->updateFinancialAccountOwner('Donation', $secondOwnerOrgId);
    $fields['financial_account_id'] = $this->donationFinancialAccountId;

    $hook = new \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount($this->form, $fields, $errors, 'CRM_Financial_Form_FinancialTypeAccount');
    $hook->handle();
    $this->assertNotEmpty($errors['financial_account_id']);
  }

  public function testUpdateFinancialTypeAccountWithOnlyOneAssignedFinancialAccountWillPassValidation() {
    $errors = [];
    $fields = [];
    $this->deleteAllFinancialTypeAccountsExceptOne();

    $this->form->setAction(\CRM_Core_Action::UPDATE);
    $this->form->setVar('_aid', $this->financialTypeId);
    $fields['financial_account_id'] = $this->accountsReceivableFinancialAccountId;

    $hook = new \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount($this->form, $fields, $errors, 'CRM_Financial_Form_FinancialTypeAccount');
    $hook->handle();
    $this->assertEmpty($errors);
  }

  public function testAddFinancialTypeAccountWithNoAssignedFinancialAccountWillPassValidation() {
    $errors = [];
    $fields = [];
    $this->deleteAllFinancialTypeAccounts();

    $this->form->setAction(\CRM_Core_Action::ADD);
    $this->form->setVar('_aid', $this->financialTypeId);
    $fields['financial_account_id'] = $this->accountsReceivableFinancialAccountId;

    $hook = new \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount($this->form, $fields, $errors, 'CRM_Financial_Form_FinancialTypeAccount');
    $hook->handle();
    $this->assertEmpty($errors);
  }

  /**
   * This function used to test the case where
   * we have only one assigned financial account type
   * since CiviCRM by default assign 4 account for the created financial type
   */
  private function deleteAllFinancialTypeAccountsExceptOne() {
    \Civi\Api4\EntityFinancialAccount::delete()
      ->addWhere('entity_table', '=', 'civicrm_financial_type')
      ->addWhere('entity_id', '=', $this->financialTypeId)
      ->addWhere('financial_account_id', '!=', $this->accountsReceivableFinancialAccountId)
      ->execute();
  }

  private function deleteAllFinancialTypeAccounts() {
    \Civi\Api4\EntityFinancialAccount::delete()
      ->addWhere('entity_table', '=', 'civicrm_financial_type')
      ->addWhere('entity_id', '=', $this->financialTypeId)
      ->execute();
  }

}
