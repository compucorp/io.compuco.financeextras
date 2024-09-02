<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use Civi\Financeextras\Setup\Manage\CreditNoteStatusManager;
use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;

/**
 * CreditNote.VoidAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_VoidActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * Test case to verify that a credit note with manual refund allocation cannot be voided.
   */
  public function testCreditNoteWithManualRefundAllocationCannotBeVoided() {
    $creditNote = $this->createCreditNote();

    $this->allocateCredit($creditNote['id'], 'manual_refund_payment', $creditNote['total_credit'] / 2);

    $this->expectException(\API_Exception::class);
    $this->expectExceptionMessage('Allocation has been made from the credit note, or doesn\'t exist');

    CreditNote::void()->setId($creditNote['id'])->execute();
  }

  /**
   * Test case to verify that a credit note with online refund allocation cannot be voided.
   */
  public function testCreditNoteWithOnlineRefundAllocationCannotBeVoided() {
    $creditNote = $this->createCreditNote();

    $this->allocateCredit($creditNote['id'], 'online_refund_payment', $creditNote['total_credit'] / 2);

    $this->expectException(\API_Exception::class);
    $this->expectExceptionMessage('Allocation has been made from the credit note, or doesn\'t exist');

    CreditNote::void()->setId($creditNote['id'])->execute();
  }

  /**
   * Test case to verify that a credit note with invoice allocation cannot be voided.
   */
  public function testCreditNoteWithInvoiceAllocationCannotBeVoided() {
    $creditNote = $this->createCreditNote();

    $this->allocateCredit($creditNote['id'], 'invoice', $creditNote['total_credit'] / 2);

    $this->expectException(\API_Exception::class);
    $this->expectExceptionMessage('Allocation has been made from the credit note, or doesn\'t exist');

    CreditNote::void()->setId($creditNote['id'])->execute();
  }

  /**
   * Test case to verify that the credit note status updates as expected when voided.
   */
  public function testCreditNoteStatusUpdatesAsExpected() {
    $creditNote = $this->createCreditNote();

    CreditNote::void()->setId($creditNote['id'])->execute();

    $creditNote = CreditNote::get()
      ->addWhere('id', '=', $creditNote['id'])
      ->execute()
      ->first();

    $voidStatus = OptionValueUtils::getValueForOptionValue(CreditNoteStatusManager::NAME, 'void');
    $this->assertEquals($voidStatus, $creditNote['status_id']);
  }

  /**
   * Test case to verify that the expected accounting entries are created for a voided credit note.
   *
   * Asserts that the credit note is associated with a valid entity financial transaction
   * and that the financial transaction meets the expected criteria.
   */
  public function testExpectedCreditNoteAccountingEntriesAreCreated() {
    $creditNote = $this->createCreditNote();
    CreditNote::void()->setId($creditNote['id'])->execute();
    $expectedToAccount = \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $creditNote['items'][0]['financial_type_id'],
      'Accounts Receivable Account is'
    );

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->addWhere('entity_id', '=', $creditNote['id'])
      ->addOrderBy('id', 'DESC')
      ->execute()
      ->first();

    $this->assertNotEmpty($entityFinancialTrxn);

    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $entityFinancialTrxn['financial_trxn_id'])
      ->addWhere('total_amount', '=', $creditNote['total_credit'])
      ->addWhere('status_id:name', '=', 'Cancelled')
      ->addWhere('payment_processor_id', 'IS NULL')
      ->addWhere('payment_instrument_id:name', '=', AccountsReceivablePaymentMethod::NAME)
      ->addWhere('check_number', 'IS NULL')
      ->addWhere('currency', '=', $creditNote['currency'])
      ->addWhere('to_financial_account_id', '=', $expectedToAccount)
      ->addWhere('from_financial_account_id', 'IS NULL')
      ->addWhere('is_payment', '=', FALSE)
      ->execute()
      ->getArrayCopy();

    $this->assertNotEmpty($financialTrxn);
    $this->assertCount(1, $financialTrxn);
  }

  /**
   * Tests that the expected accounting entries are created for the voided credit note line items with tax.
   *
   * For each credit note line entity with tax:
   *  A Financial Item should be created for the amount - tax.
   *  An Entity Financial transaction linking to financial item of the amount - tax should be created.
   *
   *  A Financial Item should be created for the tax amount.
   *  An Entity Financial transaction linking to financial item of the tax amount should be created.
   */
  public function testExpectedCreditNoteLineAccountingEntriesAreCreated() {
    $creditNoteData = $this->getCreditNoteData();
    $creditNoteData['items'][] = $this->getCreditNoteLineData(['tax_rate' => 10]);

    $creditNote = CreditNote::save()
      ->addRecord($creditNoteData)
      ->execute()
      ->first();
    CreditNote::void()->setId($creditNote['id'])->execute();

    foreach ($creditNote['items'] as $lineItem) {
      $expectedFinancialAccount = FinancialAccountUtils::getFinancialTypeAccount($lineItem['financial_type_id'], 'Income Account is');

      $financialItemAPI = \Civi\Api4\FinancialItem::get(FALSE)
        ->addWhere('entity_id', '=', $lineItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
        ->addWhere('currency', '=', $creditNote['currency'])
        ->addWhere('contact_id', '=', $creditNote['contact_id'])
        ->addWhere('status_id', '=', 1);

      // Financial item should be created for Voided credit note line item.
      $financialItemForMainAmount = (clone $financialItemAPI)->addWhere('amount', '=', ($lineItem['quantity'] * $lineItem['unit_price']))
        ->execute()
        ->first();
      $this->assertNotEmpty($financialItemForMainAmount);
      $this->assertEquals($expectedFinancialAccount, $financialItemForMainAmount['financial_account_id']);

      // Financial item should be created for Voided credit note line item tax.
      $expectedTaxFinancialAccount = FinancialAccountUtils::getFinancialTypeAccount($lineItem['financial_type_id'], 'Sales Tax Account is');
      $financialItemForTax = (clone $financialItemAPI)->addWhere('amount', '=', $lineItem['tax_amount'])
        ->execute()
        ->first();
      $this->assertNotEmpty($financialItemForTax);
      $this->assertEquals($expectedTaxFinancialAccount, $financialItemForTax['financial_account_id']);

      // Entity financial transaction should be created for both the main and tax financial item.
      foreach ([$financialItemForMainAmount, $financialItemForTax] as $financialItem) {
        $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
          ->addWhere('entity_id', '=', $financialItem['id'])
          ->addWhere('entity_table', '=', \CRM_Financial_BAO_FinancialItem::getTableName())
          ->execute()
          ->first();

        $this->assertNotEmpty($entityFinancialTrxn);
      }
    }
  }

}
