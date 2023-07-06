<?php

use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Utils\FinancialAccountUtils;
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
    $financialTrxn = self::createAccountingEntries($data, $financialTypeId, 'Pending');

    return [
      'creditNote' => $creditNote,
      'financialTrxn' => $financialTrxn,
    ];
  }

  /**
   * Creates accounting entries for a credit note.
   *
   * @param array $creditNote
   *   The credit note data.
   * @param int|null $financialTypeId
   *   The financial type for the credit note.
   * @param string $status
   *   The status to set for the transaction.
   *
   * @return array
   *   The created financial transaction.
   */
  private static function createAccountingEntries(array $creditNote, ?int $financialTypeId, string $status): array {
    $receivableAccount = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');

    $contributionStatus = \CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $statusId = array_search($status, $contributionStatus);

    $trxnParams = [
      'from_financial_account_id' => NULL,
      'to_financial_account_id' => $receivableAccount,
      'trxn_date' => $creditNote['date'],
      'total_amount' => $creditNote['total_credit'],
      'currency' => $creditNote['currency'],
      'is_payment' => 0,
      'status_id' => $statusId,
      'payment_processor_id' => NULL,
      'payment_instrument_id' => 1,
      'entity_table' => \CRM_Financeextras_DAO_CreditNote::$_tableName,
      'entity_id' => $creditNote['id'],
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

    return self::createAccountingEntries($creditNoteBAO->toArray(), $financialTypeId, 'Cancelled');
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

}
