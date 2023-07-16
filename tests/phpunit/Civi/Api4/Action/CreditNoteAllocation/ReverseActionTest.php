<?php

use Civi\Api4\LineItem;
use Civi\Api4\CreditNote;
use Civi\Api4\CreditNoteAllocation;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;

/**
 * CreditNote.ReverseAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNoteAllocation_ReverseActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * Tests that the expected accounting entries are created for allocation reversal.
   */
  public function testExpectedAccountingEntriesAreCreateForAllocatioinReversal() {
    $amountAllocated = 100;
    $amountDeAllocated = $amountAllocated * -1;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], 200);
    $allocation = $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);

    CreditNoteAllocation::reverse()
      ->setId($allocation['id'])
      ->execute();

    $expectedAccount = FinancialAccountUtils::getFinancialTypeAccount(
        $creditNote['items'][0]['financial_type_id'],
        'Accounts Receivable Account is'
      );

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('amount', '=', $amountDeAllocated)
      ->execute()
      ->first();

    $this->assertNotEmpty($entityFinancialTrxn);

    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('from_financial_account_id', '=', $expectedAccount)
      ->addWhere('to_financial_account_id', '=', $expectedAccount)
      ->addWhere('total_amount', '=', $amountDeAllocated)
      ->addWhere('id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->addWhere('status_id', '=', 7)
      ->addWhere('payment_processor_id', 'IS NULL')
      ->addWhere('payment_instrument_id', '=', 1)
      ->addWhere('check_number', 'IS NULL')
      ->execute()
      ->first();
    $this->assertNotEmpty($financialTrxn);
  }

  /**
   * Tests that the expected entity financial transactions are created for allocation reversal.
   */
  public function testExpectedEntityFinancialAccountingEntriesAreCreatedForReversal() {
    $amountAllocated = 100;
    $amountDeAllocated = $amountAllocated * -1;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], 200);
    $allocation = $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);
    $lineItems = LineItem::get()->addWhere('contribution_id', '=', $contribution['id'])->execute();

    CreditNoteAllocation::reverse()
      ->setId($allocation['id'])
      ->execute();

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('amount', '=', $amountDeAllocated)
      ->execute()
      ->first();

    $lineItemEntityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', CRM_Financial_BAO_FinancialItem::$_tableName)
      ->addWhere('financial_trxn_id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->execute();

    $this->assertEquals($lineItems->count(), $lineItemEntityFinancialTrxn->count());
  }

  /**
   * Tests that the deallocated credoit note amount reflects in the contribution due amount.
   */
  public function testContributionAmountPaidReportsAppropraiteValue() {
    $amountAllocated = 100;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], 200);
    $allocation = $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);

    $contributionPaid = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE);
    $this->assertEquals($amountAllocated, $contributionPaid);

    CreditNoteAllocation::reverse()
      ->setId($allocation['id'])
      ->execute();

    $contributionPaid = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE);
    $this->assertEquals(0, $contributionPaid);
  }

  /**
   * Tests that the credit note allocation status is set to reversed after deallocation.
   */
  public function testCreditNoteAllocationStatusIsUpdated() {
    $amountAllocated = 100;
    $creditNote = $this->createCreditNote();
    $contribution = $this->createContribution($creditNote['contact_id'], 200);
    $allocation = $this->createAllocation($creditNote['id'], $contribution['id'], $amountAllocated, $creditNote['currency']);

    $contributionPaid = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE);
    $this->assertEquals($amountAllocated, $contributionPaid);

    CreditNoteAllocation::reverse()
      ->setId($allocation['id'])
      ->execute();

    $updatedAllocation = CreditNoteAllocation::get()
      ->addWhere('id', '=', $allocation['id'])
      ->execute()
      ->first();

    $this->assertTrue($updatedAllocation['is_reversed']);
  }

  private function createCreditNote($creditAmount = 400) {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData(['quantity' => 1, 'unit_price' => $creditAmount]);

    return CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first();
  }

  private function createContribution($contactId, $contributionAmount) {
    return \Civi\Api4\Contribution::create()
      ->addValue('contact_id', $contactId)
      ->addValue('total_amount', $contributionAmount)
      ->addValue('contribution_status_id', 2)
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

}
