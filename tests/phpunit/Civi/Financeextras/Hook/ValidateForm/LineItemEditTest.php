<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use BaseHeadlessTest;

/**
 * @group headless
 */
class LineItemEditTest extends BaseHeadlessTest {

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

  public function testAllowChangingLineItemToFinancialTypeWithSameOwnerOrganization() {
    $errors = [];
    $fields = [];

    $testLineItem = civicrm_api3('LineItem', 'create', array(
      'qty' => 1,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => 1,
      'financial_type_id' => $this->donationFinancialTypeId,
      'unit_price' => 100,
      'line_total' => 100,
    ));

    $form = new \CRM_Core_Form();
    $form->setVar('_id', $testLineItem['id']);

    $fields['financial_type_id'] = $this->eventFeeFinancialTypeId;
    $hook = new \Civi\Financeextras\Hook\ValidateForm\LineItemEdit($form, $fields, $errors, 'CRM_Core_Form');
    $hook->handle();

    $this->assertEmpty($errors);
  }

  public function testPreventChangingLineItemToFinancialTypeWithDifferentOwnerOrganization() {
    $errors = [];
    $fields = [];

    $testLineItem = civicrm_api3('LineItem', 'create', array(
      'qty' => 1,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => 1,
      'financial_type_id' => $this->donationFinancialTypeId,
      'unit_price' => 100,
      'line_total' => 100,
    ));

    $form = new \CRM_Core_Form();
    $form->setVar('_id', $testLineItem['id']);

    $fields['financial_type_id'] = $this->memberDuesFinancialTypeId;
    $hook = new \Civi\Financeextras\Hook\ValidateForm\LineItemEdit($form, $fields, $errors, 'CRM_Core_Form');
    $hook->handle();

    $this->assertNotEmpty($errors['financial_type_id']);
  }

}
