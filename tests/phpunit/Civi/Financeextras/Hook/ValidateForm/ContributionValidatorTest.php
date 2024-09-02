<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use BaseHeadlessTest;

/**
 * @group headless
 */
class ContributionValidatorTest extends BaseHeadlessTest {

  private $donationFinancialTypeId;

  private $eventFeeFinancialTypeId;

  private $memberDuesFinancialTypeId;

  public function setUp(): void {
    parent::setUp();

    $this->donationFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', [
      'return' => 'id',
      'name' => 'Donation',
    ]);
    $this->eventFeeFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', [
      'return' => 'id',
      'name' => 'Event Fee',
    ]);
    $this->memberDuesFinancialTypeId = civicrm_api3('FinancialType', 'getvalue', [
      'return' => 'id',
      'name' => 'Member Dues',
    ]);

    $firstOwnerOrgId = $this->createCompany(1)['contact_id'];
    $secondOwnerOrgId = $this->createCompany(2)['contact_id'];
    $this->updateFinancialAccountOwner('Donation', $firstOwnerOrgId);
    $this->updateFinancialAccountOwner('Event Fee', $firstOwnerOrgId);
    $this->updateFinancialAccountOwner('Member Dues', $secondOwnerOrgId);
  }

  public function testAllowChangingContributionFinancialTypeToOneWithSameOwnerOrganization() {
    $errors = [];
    $fields = [];

    $testContribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);

    $form = new \CRM_Contribute_Form_Contribution();
    $form->setAction(\CRM_Core_Action::UPDATE);
    $form->setVar('_id', $testContribution['id']);

    $fields['financial_type_id'] = $this->eventFeeFinancialTypeId;
    $hook = new \Civi\Financeextras\Hook\ValidateForm\ContributionCreate($form, $fields, $errors, 'CRM_Contribute_Form_Contribution');
    $hook->handle();

    $this->assertEmpty($errors);
  }

  public function testPreventChangingContributionFinancialTypeToOneWithDifferentOwnerOrganization() {
    $errors = [];
    $fields = [];

    $testContribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);

    $form = new \CRM_Contribute_Form_Contribution();
    $form->setAction(\CRM_Core_Action::UPDATE);
    $form->setVar('_id', $testContribution['id']);

    $fields['financial_type_id'] = $this->memberDuesFinancialTypeId;
    $hook = new \Civi\Financeextras\Hook\ValidateForm\ContributionCreate($form, $fields, $errors, 'CRM_Contribute_Form_Contribution');
    $hook->handle();

    $this->assertNotEmpty($errors['financial_type_id']);
  }

  public function testAllowAddingContributionLineItemWithFinancialTypeWithSameOwnerOrganization() {
    $errors = [];
    $fields = [];

    $testContribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    $fields['financial_type_id'] = $this->donationFinancialTypeId;

    $form = new \CRM_Contribute_Form_Contribution();
    $form->setAction(\CRM_Core_Action::UPDATE);
    $form->setVar('_id', $testContribution['id']);

    $fields['item_financial_type_id'][0] = $this->eventFeeFinancialTypeId;
    $fields['item_financial_type_id'][1] = $this->donationFinancialTypeId;
    $hook = new \Civi\Financeextras\Hook\ValidateForm\ContributionCreate($form, $fields, $errors, 'CRM_Contribute_Form_Contribution');
    $hook->handle();

    $this->assertEmpty($errors);
  }

  public function testPreventAddingContributionLineItemWithFinancialTypeWithDifferentOwnerOrganization() {
    $errors = [];
    $fields = [];

    $testContribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    $fields['financial_type_id'] = $this->donationFinancialTypeId;

    $form = new \CRM_Contribute_Form_Contribution();
    $form->setAction(\CRM_Core_Action::UPDATE);
    $form->setVar('_id', $testContribution['id']);

    $fields['item_financial_type_id'][0] = $this->eventFeeFinancialTypeId;
    $fields['item_financial_type_id'][1] = $this->memberDuesFinancialTypeId;
    $hook = new \Civi\Financeextras\Hook\ValidateForm\ContributionCreate($form, $fields, $errors, 'CRM_Contribute_Form_Contribution');
    $hook->handle();

    $this->assertNotEmpty($errors['financial_type_id']);
  }

}
