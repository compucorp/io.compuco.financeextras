<?php

use Civi\Financeextras\Utils\FinancialAccountUtils;
use CRM_Financeextras_ExtensionUtil as E;

class CRM_Financeextras_BAO_CreditNoteAllocation extends CRM_Financeextras_DAO_CreditNoteAllocation {

  /**
   * Create a new CreditNoteAllocation based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_CreditNoteAllocation|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_CreditNoteAllocation';
    $entityName = 'CreditNoteAllocation';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Creates and Records the credit note allocation as payment.
   *
   * @param array $data
   *  Credit note allocation data.
   *
   * @return array
   *   New credit note created object as array
   *
   */
  public static function createWithAccountingEntries($data) {
    $transaction = CRM_Core_Transaction::create();
    try {
      $allocation = self::create($data)->toArray();

      self::createAccountingEntries($allocation['id'], $data['credit_note_id'], $data['contribution_id'], $data['amount']);
    }
    catch (\Throwable $th) {
      $transaction->rollback();

      throw $th;
    }

    return $allocation;
  }

  /**
   * Creates the neccessary accounting entries using Payment API.
   *
   * @param int $allocationId
   *  The credit note allocation ID
   *
   * @param int $creditNoteId
   *  The credit note credit is to be allocated from.
   *
   * @param int $contributionId
   *  Unique identifier of the contribtuion to allocated credit to.
   *
   * @param float $amount
   *  The amount to be allocated.
   */
  private static function createAccountingEntries($allocationId, $creditNoteId, $contributionId, $amount) {
    $date = date("Y-m-d");
    $params = [
      'contribution_id' => $contributionId,
      'total_amount' => $amount,
      'trxn_date' => $date,
      'is_send_contribution_notification' => FALSE,
      'payment_processor_id' => NULL,
    // Defaulting to 1 as payment instrument value doesn't matter for credit allocation
      'payment_instrument_id' => 1,
    ];

    $creditNoteLine = \Civi\Api4\CreditNoteLine::get()
      ->addWhere('credit_note_id.id', '=', $creditNoteId)
      ->execute()
      ->first();

    $account = FinancialAccountUtils::getFinancialTypeAccount(
      $creditNoteLine['financial_type_id'],
      'Accounts Receivable Account is'
    );
    $transaction = \CRM_Financial_BAO_Payment::create($params);

    // The Payment API typically uses the "Accounts Receivable" as the "from" account
    // and the financial account linked to the payment processor or the default
    // asset account as the "to" account. Here, we manually set both accounts
    // to the specified account ID, to ensure they are attached to epxected accounts.
    \CRM_Core_BAO_FinancialTrxn::create([
      'id' => $transaction->id,
      'from_financial_account_id' => $account,
      'to_financial_account_id' => $account,
    ]);

    self::createAllocationEntityTransactions($allocationId, $transaction->id, $amount);
  }

  /**
   * Creates entity transactions for an allocation.
   *
   * Two types ofentity financial trnsactions are created
   *  - Entity financial trnsaction to directly link line item to the finacial transanction
   *  By default CiviCRM links the the trnsaction to a finacial item and then links
   *  the financial item to the line item
   *
   * - Enity financial transaction to directly link the allocation entity to financial trnsction
   *
   * @param int $allocationId
   *   The allocation ID.
   * @param int $transactionId
   *   The transaction ID.
   * @param float $amount
   *   The amount of the allocation.
   */
  private static function createAllocationEntityTransactions($allocationId, $transactionId, $amount) {
    $finacialItemEntityTrxns = \Civi\Api4\EntityFinancialTrxn::get()
      ->addSelect('amount', 'financial_trxn_id', 'financial_item.entity_id', 'financial_item.entity_table')
      ->addJoin('FinancialItem AS financial_item', 'INNER', ['financial_item.id', '=', 'entity_id'])
      ->addWhere('entity_table', '=', 'civicrm_financial_item')
      ->addWhere('financial_trxn_id', '=', $transactionId)
      ->execute();

    foreach ($finacialItemEntityTrxns as $entityTrxn) {
      $lineItemEntityTrxn = [
        'entity_table' => $entityTrxn['financial_item.entity_table'],
        'entity_id' => $entityTrxn['financial_item.entity_id'],
        'financial_trxn_id' => $transactionId,
        'amount' => $entityTrxn['amount'],
      ];

      $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
      $entityTrxn->copyValues($lineItemEntityTrxn);
      $entityTrxn->save();
    }

    $allocationEntityTrxn = [
      'entity_table' => CRM_Financeextras_BAO_CreditNoteAllocation::$_tableName,
      'financial_trxn_id' => $transactionId,
      'entity_id' => $allocationId,
      'amount' => $amount,
    ];

    $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->copyValues($allocationEntityTrxn);
    $entityTrxn->save();
  }

}
