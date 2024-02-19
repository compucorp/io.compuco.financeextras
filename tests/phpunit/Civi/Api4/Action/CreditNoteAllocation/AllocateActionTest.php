<?php

use Civi\Api4\LineItem;
use Civi\Api4\CreditNote;
use Civi\Api4\CreditNoteAllocation;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use Civi\Financeextras\Utils\OptionValueUtils;

/**
 * CreditNote.AllocateAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNoteAllocation_AllocateActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  const CONTRIBUTION_COMPLETED = 1;
  const CONTRIBUTION_PENDING = 2;

  /**
   * Test credit note compute action returns expected fields.
   *
   * @dataProvider provideContributionStatus
   */
  public function testCanAllocateCreditNoteToContribution($status, $contributionAmount, $amountAllocated, $paid) {
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount, $status);
    $allocation = $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);

    $this->assertNotEmpty($allocation);
    $this->assertEquals(CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE), $paid);
  }

  /**
   * Tests that the expected accounting entries are created for the credit note allocation.
   *
   * @dataProvider provideContributionStatus
   */
  public function testExpectedAccountingEntriesAreCreatedForCrediNoteAllocation($status, $contributionAmount, $amountAllocated, $paid) {
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount, $status);
    $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);

    $expectedAccount = FinancialAccountUtils::getFinancialTypeAccount(
        $creditNote['items'][0]['financial_type_id'],
        'Accounts Receivable Account is'
      );

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('amount', '=', $amountAllocated)
      ->execute()
      ->first();

    $this->assertNotEmpty($entityFinancialTrxn);

    $creditNoteInstrument = OptionValueUtils::getValueForOptionValue('payment_instrument', 'credit_note');
    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('from_financial_account_id', '=', $expectedAccount)
      ->addWhere('to_financial_account_id', '=', $expectedAccount)
      ->addWhere('total_amount', '=', $amountAllocated)
      ->addWhere('id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->addWhere('status_id', '=', 1)
      ->addWhere('payment_processor_id', 'IS NULL')
      ->addWhere('payment_instrument_id', '=', $creditNoteInstrument)
      ->addWhere('check_number', 'IS NULL')
      ->execute()
      ->first();
    $this->assertNotEmpty($financialTrxn);
  }

  /**
   * Tests that the expected entity financial transactions are created as part of the accounting entries.
   *
   * @dataProvider provideContributionStatus
   */
  public function testExpectedEntityFinancialAccountingEntriesAreCreated($status, $contributionAmount, $amountAllocated, $paid) {
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount, $status);
    $allocation = $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);
    $lineItems = LineItem::get()->addWhere('contribution_id', '=', $contribution['id'])->execute();

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('amount', '=', $amountAllocated)
      ->execute()
      ->first();

    $lineItemEntityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', CRM_Financial_BAO_FinancialItem::$_tableName)
      ->addWhere('financial_trxn_id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->execute();

    $this->assertNotEmpty($lineItemEntityFinancialTrxn);
    $this->assertEquals($lineItems->count(), $lineItemEntityFinancialTrxn->count());

    $allocationEntityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $allocation['id'])
      ->addWhere('entity_table', '=', CRM_Financeextras_BAO_CreditNoteAllocation::$_tableName)
      ->addWhere('financial_trxn_id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->execute()
      ->first();

    $this->assertNotEmpty($allocationEntityFinancialTrxn);
  }

  /**
   * Tests that the contribution status is updated to partially paid
   *
   * When only part of the contribution amount is allocated.
   */
  public function testContributionStatusIsPartiallyPaidAfterPartCreditAllocation() {
    $contributionAmount = 200;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount, self::CONTRIBUTION_PENDING);
    $this->createAllocation($creditNote['id'], $contribution['id'], $contributionAmount / 2, $creditNote['currency']);

    $contribution = \Civi\Api4\Contribution::get()
      ->addSelect('contribution_status_id:name')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->first();

    $this->assertEquals('Partially paid', $contribution['contribution_status_id:name']);
  }

  /**
   * Tests that the contribution status is updated to completed
   *
   * When the amount allocated to a contribution is the full due amount.
   */
  public function testContributionStatusIsCompletedAfterFullCreditAllocation() {
    $contributionAmount = 200;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount, self::CONTRIBUTION_PENDING);
    $this->createAllocation($creditNote['id'], $contribution['id'], $contributionAmount / 2, $creditNote['currency']);
    $this->createAllocation($creditNote['id'], $contribution['id'], $contributionAmount / 2, $creditNote['currency']);

    $contribution = \Civi\Api4\Contribution::get()
      ->addSelect('contribution_status_id:name')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->first();

    $this->assertEquals('Completed', $contribution['contribution_status_id:name']);
  }

  /**
   * Tests that the completed contribution status is pending refund
   *
   * When the amount allocated to a contribution is overpaid.
   */
  public function testCompletedContributionStatusIsPendingRefund() {
    $contributionAmount = 200;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount, self::CONTRIBUTION_COMPLETED);
    $this->createAllocation($creditNote['id'], $contribution['id'], $contributionAmount / 2, $creditNote['currency']);
    $this->createAllocation($creditNote['id'], $contribution['id'], $contributionAmount / 2, $creditNote['currency']);

    $contribution = \Civi\Api4\Contribution::get()
      ->addSelect('contribution_status_id:name')
      ->addWhere('id', '=', $contribution['id'])
      ->execute()
      ->first();

    $this->assertEquals('Pending refund', $contribution['contribution_status_id:name']);
  }

  /**
   * Tests that the credit note status is updated to fully allocated.
   */
  public function testCreditNoteStatusIsFullyAllocatedAfterFullCreditAllocation() {
    $contributionAmount = 300;
    $creditNote = $this->createCreditNote(100);
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount);
    $this->createAllocation($creditNote['id'], $contribution['id'], 50, $creditNote['currency']);
    $this->createAllocation($creditNote['id'], $contribution['id'], 50, $creditNote['currency']);

    $creditNote = \Civi\Api4\CreditNote::get()
      ->addSelect('status_id:name', 'total_credit')
      ->addWhere('id', '=', $creditNote['id'])
      ->execute()
      ->first();

    $this->assertEquals('fully_allocated', $creditNote['status_id:name']);
  }

  /**
   * Tests that the credit note status is open when there's remaining credit.
   */
  public function testCreditNoteStatusIsOpenForPartCreditAllocation() {
    $contributionAmount = 300;
    $creditNote = $this->createCreditNote(100);
    $contribution = $this->createContribution($creditNote['contact_id'], $contributionAmount);
    $this->createAllocation($creditNote['id'], $contribution['id'], 50, $creditNote['currency']);

    $creditNote = \Civi\Api4\CreditNote::get()
      ->addSelect('status_id:name', 'total_credit')
      ->addWhere('id', '=', $creditNote['id'])
      ->execute()
      ->first();

    $this->assertEquals('open', $creditNote['status_id:name']);
  }

  private function createCreditNote($creditAmount = 400) {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData(['quantity' => 1, 'unit_price' => $creditAmount]);

    return CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first();
  }

  private function createContribution($contactId, $contributionAmount, $status = 2) {
    return \Civi\Api4\Contribution::create()
      ->addValue('contact_id', $contactId)
      ->addValue('total_amount', $contributionAmount)
      ->addValue('contribution_status_id', $status)
      ->addValue('financial_type_id', 1)
      ->execute()
      ->first();
  }

  private function createAllocation($creditNoteId, $contributionId, $amount, $currency) {
    $allocationType = \Civi\Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_allocation_type')
      ->addWhere('name', '=', 'invoice')
      ->execute()
      ->first()['value'];

    return CreditNoteAllocation::allocate()
      ->setContributionId($contributionId)
      ->setCreditNoteId($creditNoteId)
      ->setReference('localhost')
      ->setTypeId($allocationType)
      ->setAmount($amount)
      ->setCurrency($currency)
      ->execute();
  }

  public function provideContributionStatus() {
    return [
      'Pending Contribution' => ['status' => self::CONTRIBUTION_PENDING, 'contributionAmount' => 200, 'amountAllocated' => 100, 'paid' => 100],
      'Completed Contribtion' => ['status' => self::CONTRIBUTION_COMPLETED, 'contributionAmount' => 200, 'amountAllocated' => 100, 'paid' => 300],
    ];
  }

}
