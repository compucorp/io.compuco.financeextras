<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;

/**
 * CreditNote.DeleteWithItemsAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_DeleteWithItemsAction extends BaseHeadlessTest {

  use CreditNoteTrait;

  public function testExpectedCreditNoteAccountingEntriesAreDeleted() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNote['total_credit'] = CreditNoteBAO::computeTotalAmount($creditNote['items'])['totalAfterTax'];

    $creditNote = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->jsonSerialize()[0];

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $creditNote['id'])
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->execute()
      ->first();

    $this->assertNotEmpty($entityFinancialTrxn);

    CreditNote::deleteWithItems()
      ->addWhere('id', '=', $creditNote['id'])
      ->execute();

    $this->assertEmpty(
      \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('id', '=', $entityFinancialTrxn['id'])
        ->execute()
        ->getArrayCopy()
    );

    $this->assertEmpty(
      \Civi\Api4\FinancialTrxn::get(FALSE)
        ->addWhere('id', '=', $entityFinancialTrxn['financial_trxn_id'])
        ->execute()
        ->getArrayCopy()
    );
  }

  public function testOnlyExpectedCreditNoteAccountingEntriesAreDeleted() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $otherCreditNote = $this->getCreditNoteData();
    $otherCreditNote['items'][] = $this->getCreditNoteLineData();

    $creditNote['total_credit'] = CreditNoteBAO::computeTotalAmount($creditNote['items'])['totalAfterTax'];

    $creditNote = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->jsonSerialize()[0];

    $otherCreditNote = CreditNote::save()
      ->addRecord($otherCreditNote)
      ->execute()
      ->jsonSerialize()[0];

    $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $creditNote['id'])
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->execute()
      ->first();

    $otherEntityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_id', '=', $otherCreditNote['id'])
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->execute()
      ->first();

    CreditNote::deleteWithItems()
      ->addWhere('id', '=', $creditNote['id'])
      ->execute();

    $this->assertEmpty(
      \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('id', '=', $entityFinancialTrxn['id'])
        ->execute()
        ->getArrayCopy()
    );

    $this->assertEmpty(
      \Civi\Api4\FinancialTrxn::get(FALSE)
        ->addWhere('id', '=', $entityFinancialTrxn['financial_trxn_id'])
        ->execute()
        ->getArrayCopy()
    );

    $this->assertNotEmpty(
      \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('id', '=', $otherEntityFinancialTrxn['id'])
        ->execute()
        ->getArrayCopy()
    );

    $this->assertNotEmpty(
      \Civi\Api4\FinancialTrxn::get(FALSE)
        ->addWhere('id', '=', $otherEntityFinancialTrxn['financial_trxn_id'])
        ->execute()
        ->getArrayCopy()
    );
  }

  public function testExpectedCreditNoteLineAccountingEntriesAreDeleted() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    $creditNote['total_credit'] = CreditNoteBAO::computeTotalAmount($creditNote['items'])['totalAfterTax'];

    $creditNote = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->jsonSerialize()[0];

    $financialItemIds = [];
    $entityFinancialTrxnIds = [];

    foreach ($creditNote['items'] as $lineItem) {
      $financialItem = \Civi\Api4\FinancialItem::get(FALSE)
        ->addWhere('entity_id', '=', $lineItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
        ->execute()
        ->first();

      $financialItemIds[] = $financialItem['id'];

      $entityFinancialTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('entity_id', '=', $financialItem['id'])
        ->addWhere('entity_table', '=', \CRM_Financial_BAO_FinancialItem::$_tableName)
        ->execute()
        ->first();

      $entityFinancialTrxnIds[] = $entityFinancialTrxn['id'];
    }

    CreditNote::deleteWithItems()
      ->addWhere('id', '=', $creditNote['id'])
      ->execute();

    $this->assertEmpty(
      \Civi\Api4\CreditNoteLine::get(FALSE)
        ->addWhere('id', 'IN', array_column($creditNote['items'], 'id'))
        ->execute()
        ->getArrayCopy()
    );

    $this->assertEmpty(
      \Civi\Api4\FinancialItem::get(FALSE)
        ->addWhere('id', 'IN', $financialItemIds)
        ->execute()
        ->getArrayCopy()
    );

    $this->assertEmpty(
      \Civi\Api4\EntityFinancialTrxn::get(FALSE)
        ->addWhere('id', 'IN', $entityFinancialTrxnIds)
        ->execute()
        ->getArrayCopy()
    );
  }

}