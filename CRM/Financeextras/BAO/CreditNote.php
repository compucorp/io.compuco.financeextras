<?php

use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use CRM_Financeextras_BAO_CreditNoteAllocation as CreditNoteAllocationBAO;
use Civi\Financeextras\Setup\Manage\CreditNoteStatusManager as CreditNoteStatus;

class CRM_Financeextras_BAO_CreditNote extends CRM_Financeextras_DAO_CreditNote {

  /**
   * Create a new CreditNote based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_CreditNote|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_CreditNote';
    $entityName = 'CreditNote';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Computes the credit note line item total.
   *
   * @param array $items
   *   Array of credit note line items.
   *
   * @return array
   *   ['totalAfterTax' => <value>, 'totalBeforeTax' => <value>]
   */
  public static function computeTotalAmount(array $items) {
    $totalBeforeTax = round(array_reduce($items, fn ($a, $b) => $a + self::getLineItemSubTotal($b), 0), 2);
    $totalAfterTax = round(array_reduce($items,
      fn ($a, $b) => $a + ($b['tax_amount'] ?? (($b['tax_rate'] * self::getLineItemSubTotal($b)) / 100)),
      0
    ) + $totalBeforeTax, 2);

    return [
      'taxRates' => self::computeLineItemsTaxRates($items),
      'totalAfterTax' => $totalAfterTax,
      'totalBeforeTax' => $totalBeforeTax,
    ];
  }

  /**
   * Computes the sub total of a single line item.
   *
   * @param array $item
   *   Single credit note line item.
   *
   * @return int
   *   The line item subtotal.
   */
  private static function getLineItemSubTotal(array $item) {
    return $item['unit_price'] * $item['quantity'] ?? 0;
  }

  /**
   * Computes the tax rates of each line item.
   *
   * @param array $items
   *   Single credit note line item.
   *
   * @return array
   *   Returned sorted array of line items tax rates.
   */
  private static function computeLineItemsTaxRates(array $items) {
    $items = array_filter($items, fn ($a) => !empty($a['tax_rate']) && $a['tax_rate'] > 0);
    usort($items, fn ($a, $b) => $a['tax_rate'] <=> $b['tax_rate']);

    return array_map(
      fn ($a) =>
      [
        'tax_name' => $a['tax_name'] ?? '',
        'rate' => round($a['tax_rate'], 2),
        'value' => round(($a['tax_rate'] * self::getLineItemSubTotal($a)) / 100, 2),
      ],
      $items
    );
  }

  /**
   * Creates a credit note with accounting entries.
   *
   * @param array $data
   *   The credit note data.
   * @param int|null $financialTypeId
   *   The financial type for the first credit note line.
   *
   * @return array
   *   An array containing the created credit note and its financial transaction.
   */
  public static function createWithAccountingEntries(array $data, ?int $financialTypeId): array {
    $creditNote = self::create($data)->toArray();
    $data = array_merge($creditNote, ['total_credit' => $creditNote['total_credit'] * -1]);

    $data['to_account_id'] = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');
    $financialTrxn = self::createAccountingEntries($data, 'Pending');

    return [
      'creditNote' => $creditNote,
      'financialTrxn' => $financialTrxn,
    ];
  }

  /**
   * Creates accounting entries for a credit note.
   *
   * @param array $data
   *   The credit note and payment data.
   * @param string $status
   *   The status to set for the transaction.
   *
   * @return array
   *   The created financial transaction.
   */
  private static function createAccountingEntries(array $data, string $status): array {
    $contributionStatus = \CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $statusId = array_search($status, $contributionStatus);

    $trxnParams = [
      'from_financial_account_id' => $data['from_account_id'] ?? NULL,
      'to_financial_account_id' => $data['to_account_id'] ?? NULL,
      'trxn_date' => $data['date'],
      'total_amount' => $data['total_credit'],
      'currency' => $data['currency'],
      'is_payment' => $data['is_payment'] ?? 0,
      'status_id' => $statusId,
      'payment_processor_id' => NULL,
      'payment_instrument_id' => $data['payment_instrument_id'] ?? 1,
      'card_type_id' => $data['card_type_id'] ?? NULL,
      'check_number' => $data['check_number'] ?? NULL,
      'pan_truncation' => $data['pan_truncation'] ?? NULL,
      'trxn_id' => $data['trxn_id'] ?? NULL,
      'fee_amount' => $data['fee_amount'] ?? NULL,
      'entity_table' => \CRM_Financeextras_DAO_CreditNote::$_tableName,
      'entity_id' => $data['id'],
    ];
    return \CRM_Core_BAO_FinancialTrxn::create($trxnParams)->toArray();
  }

  /**
   * Voids a credit note.
   *
   * @param int $creditNoteId
   *   The ID of the credit note to void.
   * @param int|null $financialTypeId
   *   The financial type for the credit note.
   * @return array
   *   The voided financial transaction.
   */
  public static function voidCreditNote(int $creditNoteId, ?int $financialTypeId): array {
    $creditNoteBAO = new CRM_Financeextras_DAO_CreditNote();
    $creditNoteBAO->id = $creditNoteId;
    $creditNoteBAO->find(TRUE);

    $creditNoteBAO->status_id = OptionValueUtils::getValueForOptionValue(CreditNoteStatus::NAME, 'void');
    $creditNoteBAO->update();

    $data = $creditNoteBAO->toArray();
    $data['to_account_id'] = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');
    return self::createAccountingEntries($data, 'Cancelled');
  }

  /**
   * Deletes credit note accounting entries.
   *
   * @param int $creditNoteId
   *  The credit note unique identifier.
   */
  public static function deleteAccountingEntries($creditNoteId): void {
    $entityTrxn = new \CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->entity_table = \CRM_Financeextras_DAO_CreditNote::$_tableName;
    $entityTrxn->entity_id = $creditNoteId;
    $entityTrxn->find(TRUE);

    $trxn = new \CRM_Financial_DAO_FinancialTrxn();
    $trxn->deleteRecord(['id' => $entityTrxn->financial_trxn_id]);

    $entityTrxn->delete();
  }

  /**
   * Updates a cedit note status appropraitely post allocation
   *
   * @param int $allocationId
   *  The credit note for which allocation was made.
   */
  public static function updateCreditNoteStatusPostAllocation($allocationId) {
    $allocation = \Civi\Api4\CreditNoteAllocation::get()
      ->addWhere('id', '=', $allocationId)
      ->addChain('credit_note', \Civi\Api4\CreditNote::get()
        ->addWhere('id', '=', '$credit_note_id')
        ->addWhere('status_id:name', 'IN', ['open', 'fully_allocated'])
        )
      ->execute()
      ->first();
    $creditNote = $allocation['credit_note'][0] ?? NULL;

    if (empty($creditNote)) {
      return;
    }

    $status = match(TRUE) {
      $creditNote['remaining_credit'] <= 0 => 'fully_allocated',
      default => 'open'
    };

    \Civi\Api4\CreditNote::update()
      ->addValue('status_id:name', $status)
      ->addWhere('id', '=', $creditNote['id'])
      ->execute();
  }

  /**
   * Refund credit note and create assocaited accounting entries.
   *
   * @param int $creditNoteId
   *   The ID of the credit note to void.
   * @param array $allocationParam
   *
   * @param array $paymentParam
   *  Payment Parameters
   *
   * @return array
   *   The credit note refund allocation data
   */
  public static function refund($creditNoteId, $allocationParam, $paymentParam) {
    $creditNote = \Civi\Api4\CreditNote::get()
      ->addWhere('id', '=', $creditNoteId)
      ->addChain('line', \Civi\Api4\CreditNoteLine::get()
        ->addWhere('credit_note_id', '=', '$id')
      )
      ->execute()
      ->first();

    if ($creditNote['remaining_credit'] < $allocationParam['amount']) {
      throw new CRM_Core_Exception("Amount to be refunded cannot exceed the remaining credit");
    }
    $financialTrxn = self::createRefundAccountingEntries($creditNote, array_merge($allocationParam, $paymentParam));
    $allocation = self::createRefundAllocation($creditNote, $allocationParam);
    CreditNoteAllocationBAO::createAllocationEntityTransactions($allocation['id'], $financialTrxn['id'], -$allocationParam['amount']);

    return array_merge($allocation, ['financial_trxn_id' => $financialTrxn['id']]);
  }

  private static function createRefundAllocation(array $creditNote, array $data) {
    return \Civi\Api4\CreditNoteAllocation::create()
      ->addValue('credit_note_id', $creditNote['id'])
      ->addValue('type_id:name', 'manual_refund_payment')
      ->addValue('currency', $creditNote['currency'])
      ->addValue('reference', $data['reference'])
      ->addValue('amount', $data['amount'])
      ->addValue('date', $data['date'])
      ->execute()
      ->first();
  }

  private static function createRefundAccountingEntries(array $creditNote, $data) {
    $data = array_merge($creditNote, ['total_credit' => $data['amount'] * -1], $data);
    $financialTypeId = $creditNote['line'][0]['financial_type_id'];
    $data['from_account_id'] = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');
    $data['to_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($data['payment_instrument_id']);
    $data['is_payment'] = 1;
    $financialTrxn = self::createAccountingEntries($data, 'Completed');

    $ratio = $data['amount'] / $creditNote['total_credit'];

    foreach ($creditNote['line'] as $creditNoteLine) {
      $amount = $creditNoteLine['line_total'] * $ratio * -1;
      CRM_Financeextras_BAO_CreditNoteLine::refundAccountingEntries($creditNoteLine['id'], $financialTrxn['id'], $amount);
    }

    return $financialTrxn;
  }

}
