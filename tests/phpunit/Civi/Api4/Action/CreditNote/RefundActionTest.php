<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;

/**
 * CreditNote.RefundAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_RefundActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  public function testCanRefundCreditNote() {
    $creditNote = $this->createCreditNote(500);
    $refundAllocation = $this->createRefund($creditNote['id']);

    $this->assertNotEmpty($refundAllocation);
    $this->assertEquals($refundAllocation['credit_note_id'], $creditNote['id']);
  }

  /**
   * Assert the refund amount cannot be greatger than the remaining credit
   */
  public function testExceptionIsThrownForInvalidRefundAmount() {
    $amountToRefund = 200;
    $creditNote = $this->createCreditNote(500);
    $this->allocateCredit($creditNote['id'], 'invoice', 400);

    $this->expectException(CRM_Core_Exception::class);

    $this->createRefund($creditNote['id'], $amountToRefund);
  }

  /**
   * Tests that the expected entity financial transactions are created as part of the accounting entries.
   */
  public function testExpectedRefundEntityFinancialAccountingEntriesAreCreated() {
    $amountToRefund = 200;
    $creditNote = $this->createCreditNote(500);

    $refundAllocation = $this->createRefund($creditNote['id'], $amountToRefund, [
      'payment_instrument_id' => 3,
      'check_number' => '1234',
    ]);

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $creditNote['id'])
      ->addWhere('entity_table', '=', CRM_Financeextras_BAO_CreditNote::$_tableName)
      ->addWhere('amount', '=', -$amountToRefund)
      ->addWhere('financial_trxn_id', '=', $refundAllocation['financial_trxn_id'])
      ->execute()
      ->getArrayCopy();

    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('total_amount', '=', -1 * $amountToRefund)
      ->addWhere('id', '=', $refundAllocation['financial_trxn_id'])
      ->addWhere('status_id', '=', 1)
      ->addWhere('payment_processor_id', 'IS NULL')
      ->addWhere('payment_instrument_id', '=', 3)
      ->addWhere('check_number', '=', '1234')
      ->addWhere('is_payment', '=', 1)
      ->addWhere('check_number', '=', '1234')
      ->execute()
      ->first();
    $this->assertNotEmpty($financialTrxn);

    $this->assertNotEmpty($entityFinancialTrxn);
  }

  public function testRefundFinancialTrxnHasExpectedToandFromAccountId() {
    $creditNote = $this->createCreditNote(500);
    $refundAllocation = $this->createRefund($creditNote['id']);

    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $refundAllocation['financial_trxn_id'])
      ->setLimit(1)
      ->execute()
      ->first();

    $expectedFromAccount = \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $creditNote['items'][0]['financial_type_id'],
      'Accounts Receivable Account is'
    );
    $expectedToAccount = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount(1);

    $this->assertEquals($financialTrxn['to_financial_account_id'], $expectedToAccount);
    $this->assertEquals($financialTrxn['from_financial_account_id'], $expectedFromAccount);
  }

  public function testExpectedLineItemEntityTrxnRecordIsCreatedForRefund() {
    $amountToRefund = 150;
    $creditNote = $this->createCreditNote(500);
    $refundAllocation = $this->createRefund($creditNote['id'], $amountToRefund);

    $lineItemSum = array_reduce($creditNote['items'], function($previousSum, $currentLine) use ($refundAllocation) {
      $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('entity_id', '=', $currentLine['id'])
        ->addWhere('entity_table', '=', CRM_Financeextras_BAO_CreditNoteLine::$_tableName)
        ->addWhere('financial_trxn_id', '=', $refundAllocation['financial_trxn_id'])
        ->execute()
        ->first();

      return $previousSum + $entityFinancialTrxn['amount'];
    });

    $this->assertEquals(-1 * $amountToRefund, $lineItemSum);
  }

  private function createRefund($creditNoteId, $amountToRefund = 200, $paymentParam = []) {
    return \Civi\Api4\CreditNote::refundAction()
      ->setId($creditNoteId)
      ->setAmount($amountToRefund)
      ->setReference('reference')
      ->setDate(date('Y-m-d'))
      ->setPaymentParam(array_merge([
        'payment_instrument_id' => 1,
        'credit_card_type' => 'Visa',
        'pan_truncation' => "2333",
        'trxn_id' => mt_rand(1000, 6000),
        'fee_amount' => '1.50',
      ], $paymentParam))
      ->execute()
      ->first();
  }

  private function createCreditNote($creditAmount = 400) {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData(['quantity' => 1, 'unit_price' => $creditAmount / 2]);
    $creditNote['items'][] = $this->getCreditNoteLineData(['quantity' => 1, 'unit_price' => $creditAmount / 2]);

    return CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first();
  }

}
