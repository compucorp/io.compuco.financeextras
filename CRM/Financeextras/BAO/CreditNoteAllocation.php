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
    $creditNoteAllocation = self::create($data)->toArray();

    self::createAccountingEntries($data['credit_note_id'], $data['contribution_id'], $data['amount']);

    return $creditNoteAllocation;
  }

  /**
   * Creates the neccessary accounting entries using Payment API.
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
  private static function createAccountingEntries($creditNoteId, $contributionId, $amount) {
    $date = date("Y-m-d");
    $params = [
      'contribution_id' => $contributionId,
      'total_amount' => $amount,
      'trxn_date' => $date,
      'is_send_contribution_notification' => FALSE,
      'payment_processor_id' => NULL,
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
    \CRM_Core_BAO_FinancialTrxn::create([
      'id' => $transaction->id,
      'from_financial_account_id' => $account,
      'to_financial_account_id' => $account,
    ]);
  }

}
