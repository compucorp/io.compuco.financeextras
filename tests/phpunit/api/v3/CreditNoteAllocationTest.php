<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use Civi\Financeextras\Utils\OptionValueUtils;

/**
 * Tests for the CreditNoteAllocation APIv3 wrapper.
 *
 * @group headless
 */
class api_v3_CreditNoteAllocationTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * The full credit note total used by most tests.
   *
   */
  const CREDIT_TOTAL = 200;

  /**
   * Valid create call records an allocation plus the standard accounting entries.
   */
  public function testCreateRecordsAllocationAndAccountingEntries() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 200);

    $result = civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 100,
      'reference' => 'IMPORTER-TEST',
    ]);

    $this->assertEquals(0, $result['is_error']);
    $this->assertNotEmpty($result['id']);

    $allocation = \Civi\Api4\CreditNoteAllocation::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->execute()
      ->first();
    $this->assertNotEmpty($allocation);
    $this->assertEquals($creditNote['id'], $allocation['credit_note_id']);
    $this->assertEquals(100, $allocation['amount']);

    $entityTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', CRM_Financeextras_BAO_CreditNoteAllocation::$_tableName)
      ->addWhere('entity_id', '=', $allocation['id'])
      ->execute();
    $this->assertGreaterThan(0, $entityTrxn->count());
  }

  /**
   * The pseudo-constant resolver should allow to pass the option value name (e.g. "invoice").
   */
  public function testTypeIdCanBeSuppliedByName() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 200);

    $result = civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_name' => 'invoice',
      'currency' => 'GBP',
      'amount' => 50,
    ]);

    $this->assertEquals(0, $result['is_error']);

    $allocation = \Civi\Api4\CreditNoteAllocation::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->addSelect('type_id:name')
      ->execute()
      ->first();
    $this->assertEquals('invoice', $allocation['type_id:name']);
  }

  /**
   * A negative or zero amount must be rejected before any DB write.
   */
  public function testCreateRejectsNonPositiveAmount() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 200);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/greater than zero/i');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 0,
    ]);
  }

  /**
   * A single allocation that asks for more than the total credit must be rejected.
   */
  public function testCreateRejectsAmountExceedingTotalCredit() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 1000);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/exceeds the remaining credit/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => self::CREDIT_TOTAL + 1,
    ]);
  }

  /**
   * Total allocations must not exceed the credit note's total.
   */
  public function testCreateRejectsCumulativeAmountExceedingTotalCredit() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 1000);

    // First allocation (150 of 200) succeeds.
    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 150,
    ]);

    // Second allocation would push us to 250 - over the 200 limit.
    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/exceeds the remaining credit/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 100,
    ]);
  }

  /**
   * A non-existent credit_note_id must produce an actionable error.
   *
   * A valid contribution_id is supplied so the spec-level api.required
   * check on contribution_id passes; we want this test to exercise the
   * credit-note-existence check that runs after spec validation, not
   * the missing-required-field path.
   */
  public function testCreateRejectsUnknownCreditNoteId() {
    $contact = ContactFabricator::fabricate([
      'first_name' => 'Unknown CN',
      'last_name' => 'Tester',
    ]);
    $contribution = $this->createContribution($contact['id'], 100);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/Credit note with id .* does not exist/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => 999999999,
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 10,
    ]);
  }

  /**
   * The credit note and the contribution must belong to the same
   * contact. Allocating across contacts is almost certainly a CSV
   * mistake and is refused with an actionable message.
   */
  public function testCreateRejectsCrossContactAllocation() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $otherContact = ContactFabricator::fabricate([
      'first_name' => 'Other',
      'last_name' => 'Customer',
    ]);
    $contribution = $this->createContribution($otherContact['id'], 200);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/must belong to the same contact/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 50,
    ]);
  }

  /**
   * A credit note with no contact bound has no anchor against which
   * the contribution's contact can be checked, so allocation is
   * rejected outright.
   */
  public function testCreateRejectsAllocationWhenCreditNoteHasNoContact() {
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 200);

    // Strip the contact off the credit note to simulate a credit note
    // that was created without one (the schema permits NULL).
    \CRM_Core_DAO::executeQuery(
      'UPDATE financeextras_credit_note SET contact_id = NULL WHERE id = %1',
      [1 => [$creditNote['id'], 'Integer']]
    );

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/has no contact set/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 50,
    ]);
  }

  /**
   * The allocation row's currency must equal the credit note's
   * currency. Otherwise we'd silently misrepresent amounts in the
   * accounting entries.
   */
  public function testCreateRejectsCurrencyMismatchAgainstCreditNote() {
    // Credit note is GBP (default in getCreditNoteData), contribution
    // is also GBP, but the allocation row supplies USD.
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 200);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/Currency mismatch for credit note/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'USD',
      'amount' => 50,
    ]);
  }

  /**
   * The allocation row's currency must equal the target contribution's
   * currency too - the credit note and contribution can theoretically
   * disagree, but the allocation can only point at one of them, so we
   * insist on a consistent triple.
   */
  public function testCreateRejectsCurrencyMismatchAgainstContribution() {
    // Credit note is GBP, allocation row is GBP, but the contribution
    // is in USD.
    $creditNote = $this->buildCreditNoteWithTotal(self::CREDIT_TOTAL);
    $contribution = $this->createContribution($creditNote['contact_id'], 200, 'USD');

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/Currency mismatch for contribution/');

    civicrm_api3('CreditNoteAllocation', 'create', [
      'credit_note_id' => $creditNote['id'],
      'contribution_id' => $contribution['id'],
      'type_id' => $this->getAllocationTypeValue('invoice'),
      'currency' => 'GBP',
      'amount' => 50,
    ]);
  }

  /**
   * Builds a credit note with a single line.
   */
  private function buildCreditNoteWithTotal(int $creditTotal): array {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData([
      'quantity' => 1,
      'unit_price' => $creditTotal,
      'tax_rate' => 0,
    ]);

    return CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->first();
  }

  private function createContribution(int $contactId, $amount, string $currency = 'GBP'): array {
    return \Civi\Api4\Contribution::create()
      ->addValue('contact_id', $contactId)
      ->addValue('total_amount', $amount)
      ->addValue('currency', $currency)
      // 2 = Pending - matches what AllocateActionTest uses for invoice
      // allocations.
      ->addValue('contribution_status_id', 2)
      ->addValue('financial_type_id', 1)
      ->execute()
      ->first();
  }

  private function getAllocationTypeValue(string $name) {
    return OptionValueUtils::getValueForOptionValue(
      'financeextras_credit_note_allocation_type',
      $name
    );
  }

}
