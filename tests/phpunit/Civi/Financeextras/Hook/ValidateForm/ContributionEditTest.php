<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use BaseHeadlessTest;
use Civi\Api4\Contribution;
use Civi\Api4\FinancialType;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * @group headless
 */
class ContributionEditTest extends BaseHeadlessTest {

  private $donationFinancialTypeId;

  private $eventFeeFinancialTypeId;

  private $contribution;

  public function setUp(): void {
    parent::setUp();

    $this->donationFinancialTypeId = $this->getFinancialTypeId('Donation');
    $this->eventFeeFinancialTypeId = $this->getFinancialTypeId('Event Fee');

    $contact = ContactFabricator::fabricate();
    $this->contribution = ContributionFabricator::fabricate([
      'financial_type_id' => $this->donationFinancialTypeId,
      'receive_date' => date('Y-m-d'),
      'total_amount' => 100,
      'contact_id' => $contact['id'],
      'currency' => 'GBP',
    ]);
  }

  public function testNoErrorWhenFinancialTypeIsUnchanged() {
    $errors = [];
    $fields = ['financial_type_id' => $this->donationFinancialTypeId];

    $this->runHook($fields, $errors);

    $this->assertEmpty($errors);
  }

  public function testNoErrorWhenSubmittedFinancialTypeMatchesTheUpdatedContribution() {
    Contribution::update(FALSE)
      ->addWhere('id', '=', $this->contribution['id'])
      ->addValue('financial_type_id', $this->eventFeeFinancialTypeId)
      ->execute();

    $errors = [];
    $fields = ['financial_type_id' => $this->eventFeeFinancialTypeId];

    $this->runHook($fields, $errors);

    $this->assertEmpty($errors);
  }

  public function testErrorWhenFinancialTypeIsChangedOnTheForm() {
    $errors = [];
    $fields = ['financial_type_id' => $this->eventFeeFinancialTypeId];

    $this->runHook($fields, $errors);

    $this->assertArrayHasKey('financial_type_id', $errors);
    $this->assertEquals(
      'One or more line items have a different financial type than the contribution. Editing the financial type is not yet supported in this situation.',
      $errors['financial_type_id']
    );
  }

  private function getFinancialTypeId(string $name): int {
    return FinancialType::get(FALSE)
      ->addSelect('id')
      ->addWhere('name', '=', $name)
      ->execute()
      ->first()['id'];
  }

  private function runHook(array &$fields, array &$errors) {
    $form = new \CRM_Contribute_Form_Contribution();
    $form->_id = $this->contribution['id'];

    $hook = new ContributionEdit($form, $fields, $errors, 'CRM_Contribute_Form_Contribution');
    $hook->handle();
  }

}
