<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;

/**
 * CreditNote.CreditNoteGetAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_GetActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  public function testCreditNoteGetReturnsIncludeAllocatedFields() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $result = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    $this->assertArrayHasKey('allocated_invoice', $result);
    $this->assertArrayHasKey('allocated_manual_refund', $result);
    $this->assertArrayHasKey('allocated_online_refund', $result);
    $this->assertArrayHasKey('remaining_credit', $result);
  }

  public function testCreditNoteGetReturnsExpectedAllocatedInvoice() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $otherCreditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $this->allocateToCreditNote($creditNoteId, 'invoice', 200);
    $this->allocateToCreditNote($creditNoteId, 'invoice', 300);
    $this->allocateToCreditNote($otherCreditNoteId, 'invoice', 200);

    $result = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    $this->assertEquals($result['allocated_invoice'], 500);
  }

  public function testCreditNoteGetReturnsExpectedAllocatedManualRefund() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $otherCreditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $this->allocateToCreditNote($creditNoteId, 'manual_refund_payment', 200);
    $this->allocateToCreditNote($creditNoteId, 'manual_refund_payment', 100);
    $this->allocateToCreditNote($otherCreditNoteId, 'manual_refund_payment', 200);

    $result = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    $this->assertEquals($result['allocated_manual_refund'], 300);
  }

  public function testCreditNoteGetReturnsExpectedAllocatedOnlineRefund() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $otherCreditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $this->allocateToCreditNote($creditNoteId, 'online_refund_payment', 200);
    $this->allocateToCreditNote($creditNoteId, 'online_refund_payment', 200);
    $this->allocateToCreditNote($otherCreditNoteId, 'online_refund_payment', 200);

    $result = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    $this->assertEquals($result['allocated_online_refund'], 400);
  }

  public function testCreditNoteGetReturnsExpectedRemainingCredit() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData(['quantity' => 2, 'unit_price' => 600]);

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $otherCreditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $this->allocateToCreditNote($creditNoteId, 'invoice', 100);
    $this->allocateToCreditNote($creditNoteId, 'online_refund_payment', 200);
    $this->allocateToCreditNote($creditNoteId, 'manual_refund_payment', 300);
    $this->allocateToCreditNote($otherCreditNoteId, 'online_refund_payment', 200);

    $result = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    $this->assertEquals($result['remaining_credit'], 600);
  }

  public function testCreditNoteGetNotIncludeRemainingCreditWithoutTotalCredit() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->first()['id'];

    $this->allocateToCreditNote($creditNoteId, 'invoice', 100);

    $result = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->addSelect('id')
      ->execute()
      ->first();

    $this->assertArrayHasKey('allocated_invoice', $result);
    $this->assertArrayHasKey('allocated_manual_refund', $result);
    $this->assertArrayHasKey('allocated_online_refund', $result);
    $this->assertArrayNotHasKey('remaining_credit', $result);
  }

  private function allocateToCreditNote($creditNoteId, $allocationType, $amount) {
    $type = \Civi\Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_allocation_type')
      ->addWhere('name', '=', $allocationType)
      ->execute()
      ->first()['value'];

    \Civi\Api4\CreditNoteAllocation::create()
      ->addValue('credit_note_id', $creditNoteId)
      ->addValue('type_id', $type)
      ->addValue('currency', 'GBP')
      ->addValue('amount', $amount)
      ->execute();
  }

}
