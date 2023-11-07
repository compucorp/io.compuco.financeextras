<?php

use Civi\Api4\CreditNoteLine;
use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;

/**
 * CreditNote.CreditNoteSaveAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_CreditNoteSaveActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * Test credit note and line item can be saved with the save action.
   */
  public function testCanSaveCreditNote() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNote['total_credit'] = CreditNoteBAO::computeTotalAmount($creditNote['items'])['totalAfterTax'];

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->jsonSerialize()[0]['id'];

    $results = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->jsonSerialize();

    $this->assertNotEmpty($results);
    foreach (['contact_id', 'comment', 'total_credit', 'reference'] as $key) {
      $this->assertEquals($creditNote[$key], $results[0][$key]);
    }
  }

  /**
   * Test credit note and line item can be saved with the save action.
   */
  public function testCanSaveCreditNoteAndLineItems() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->jsonSerialize()[0]['id'];

    $results = CreditNoteLine::get()
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute()
      ->jsonSerialize();

    $this->assertCount(2, $results);
    foreach ($results as $result) {
      $this->assertEquals($result['credit_note_id'], $creditNoteId);
    }
  }

  /**
   * Test credit note total is calculated appropraitely.
   */
  public function testSaveCreditNoteTotalIsCorrect() {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData(
      ['quantity' => 10, 'unit_price' => 10, 'tax_rate' => 10]
    );
    $creditNoteData['items'][] = $this->getCreditNoteLineData(
      ['quantity' => 5, 'unit_price' => 10]
    );

    $creditNoteId = CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->jsonSerialize()[0]['id'];

    $creditNote = CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->jsonSerialize()[0];

    $this->assertEquals($creditNote['subtotal'], 150);
    $this->assertEquals($creditNote['total_credit'], 160);
  }

  /**
   * Test credit note save action updates credit note as expected.
   */
  public function testCreditNoteIsUpdatedWithSaveAction() {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData();

    // Create credit note.
    $creditNote = CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->jsonSerialize()[0];

    // Update credit note.
    $creditNoteData['id'] = $creditNote['id'];
    $creditNoteData['items'] = $creditNote['items'];
    $creditNoteData['comment'] = substr(md5(mt_rand()), 0, 7);
    $creditNoteData['description'] = substr(md5(mt_rand()), 0, 7);
    CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->jsonSerialize()[0];

    // Assert that credit note was updated.
    $updatedSalesOrder = CreditNote::get()
      ->addWhere('id', '=', $creditNote['id'])
      ->execute()
      ->jsonSerialize()[0];

    $this->assertEquals($creditNoteData['id'], $updatedSalesOrder['id']);
    $this->assertEquals($creditNoteData['comment'], $updatedSalesOrder['comment']);
    $this->assertEquals($creditNoteData['description'], $updatedSalesOrder['description']);
  }

  /**
   * Tests that the expected accounting entries are created for the credit note.
   *
   * For a credit note entity:
   *  A Finacial trnasaction should be created.
   *  A Entity Financial transaction linked to credit note  should be created.
   */
  public function testExpectedAccountingEntriesAreCreatedForCrediNote() {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData();

    $expectedToAccount = \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $creditNoteData['items'][0]['financial_type_id'],
      'Accounts Receivable Account is'
    );

    // Create credit note.
    $creditNote = CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->jsonSerialize()[0];

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $creditNote['id'])
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->execute()
      ->first();

    $this->assertNotEmpty($entityFinancialTrxn);

    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->addWhere('total_amount', '=', $creditNote['total_credit'] * -1)
      ->addWhere('status_id:name', '=', 'Pending')
      ->addWhere('payment_processor_id', 'IS NULL')
      ->addWhere('payment_instrument_id', '=', 1)
      ->addWhere('check_number', 'IS NULL')
      ->addWhere('currency', '=', $creditNote['currency'])
      ->addWhere('to_financial_account_id', '=', $expectedToAccount)
      ->addWhere('is_payment', '=', FALSE)
      ->execute()
      ->first();

    $this->assertNotEmpty($financialTrxn);
  }

  /**
   * Tests that the expected accounting entries are created for the credit note line items.
   *
   * For each credit note line entity:
   *  A Financial Item should be created.
   *  A Entity Financial transaction linking to financial item should be created.
   */
  public function testExpectedAccountingEntriesAreCreatedForCrediNoteLine() {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData();

    // Create credit note.
    $creditNote = CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->first();

    foreach ($creditNote['items'] as $lineItem) {
      // Credit note line item a financial item should be created.
      $financialItem = \Civi\Api4\FinancialItem::get(FALSE)
        ->addWhere('entity_id', '=', $lineItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
        ->execute()
        ->first();

      $this->assertNotEmpty($financialItem);
      $this->assertEquals($financialItem['amount'] * -1, $lineItem['quantity'] * $lineItem['unit_price']);
      $this->assertEquals($financialItem['contact_id'], $creditNote['contact_id']);
      $this->assertEquals($financialItem['currency'], $creditNote['currency']);

      // Financial item should have a entity financial transaction.
      $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('entity_id', '=', $financialItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financial_BAO_FinancialItem::$_tableName)
        ->execute()
        ->first();

      $this->assertNotEmpty($entityFinancialTrxn);
    }
  }

  /**
   * Tests that the expected accounting entries are created for the credit note line items with tax.
   *
   * For each credit note line entity with tax:
   *  A Financial Item should be created for the amount - tax.
   *  A Entity Financial transaction linking to financial item of the amount - tax should be created.
   *
   *  A Financial Item should be created for the tax amount.
   *  A Entity Financial transaction linking to financial item of the tax amount should be created.
   */
  public function testExpectedAccountingEntriesAreCreatedForCrediNoteLineWithTax() {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData(['tax_rate' => 10]);
    $creditNoteData['items'][] = $this->getCreditNoteLineData(['tax_rate' => 15]);

    // Create credit note.
    $creditNote = CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->first();

    foreach ($creditNote['items'] as $lineItem) {
      // Financial item should be created for Credit note line item.
      $financialItemAPI = \Civi\Api4\FinancialItem::get(FALSE)
        ->addWhere('entity_id', '=', $lineItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
        ->addWhere('currency', '=', $creditNote['currency'])
        ->addWhere('contact_id', '=', $creditNote['contact_id'])
        ->addWhere('status_id', '=', 3);

      $all = \Civi\Api4\FinancialItem::get(FALSE)
        ->addWhere('entity_id', '=', $lineItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
        ->addWhere('currency', '=', $creditNote['currency'])
        ->addWhere('contact_id', '=', $creditNote['contact_id'])->execute();

      $financialItemForMainAmount = (clone $financialItemAPI)->addWhere('amount', '=', ($lineItem['quantity'] * $lineItem['unit_price']) * -1)
        ->execute()
        ->first();
      $this->assertNotEmpty($financialItemForMainAmount);

      $financialItemForTax = (clone $financialItemAPI)->addWhere('amount', '=', $lineItem['tax_amount'] * -1)
        ->execute()
        ->first();
      $this->assertNotEmpty($financialItemForTax);

      foreach ([$financialItemForMainAmount, $financialItemForTax] as $financialItem) {
        $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
          ->addWhere('entity_id', '=', $financialItem['id'])
          ->addWhere('entity_table', '=', \CRM_Financial_BAO_FinancialItem::$_tableName)
          ->execute()
          ->first();

        // Financial item should have a entity financial transaction.
        $this->assertNotEmpty($entityFinancialTrxn);
      }
    }
  }

}
