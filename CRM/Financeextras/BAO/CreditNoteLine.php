<?php

use Civi\Financeextras\Utils\FinancialAccountUtils;

class CRM_Financeextras_BAO_CreditNoteLine extends CRM_Financeextras_DAO_CreditNoteLine {

  /**
   * Create a new CreditNoteLine based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_CreditNoteLine|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_CreditNoteLine';
    $entityName = 'CreditNoteLine';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Creates credit note line items and their corresponding accounting entry.
   *
   * @param array $items
   *  Array of line items.
   *
   * @param array $creditNote
   *  The credit note entity to create line items for
   *
   * @param array $financialTrxn
   *  The credit note financial transaction entity.
   *
   * @return array
   *   Created line items.
   */
  public static function createWithAcountingEntries($items, $creditNote, $financialTrxn) {
    $result = [];
    array_walk($items, function (&$lineItem) use ($creditNote, $financialTrxn, &$result) {
      $lineTotal = $lineItem['unit_price'] * $lineItem['quantity'];
      $lineItemParams = [
        'credit_note_id' => $creditNote['id'],
        'description' => $lineItem['description'],
        'quantity' => $lineItem['quantity'],
        'unit_price' => $lineItem['unit_price'],
        'tax_amount' => empty($lineItem['tax_rate']) ? 0 : self::calculateTaxAmount($lineItem['tax_rate'], $lineTotal),
        'line_total' => $lineTotal,
        'financial_type_id' => $lineItem['financial_type_id'],
      ];

      $lineItem = self::create($lineItemParams)->toArray();
      self::createAccountingEntries($lineItem, $creditNote, $financialTrxn);

      $result[] = $lineItem;
    });

    return $result;
  }

  /**
   * Deletes credit notes line accounting entries.
   *
   * @param int $creditNoteLineId
   *  The credit note line unique identifier.
   */
  public static function deleteAccountingEntries($creditNoteLineId) {
    $financialItem = new \CRM_Financial_BAO_FinancialItem();
    $financialItem->entity_table = \CRM_Financeextras_DAO_CreditNoteLine::$_tableName;
    $financialItem->entity_id = $creditNoteLineId;
    $financialItem->find(TRUE);

    $entityTrxn = new \CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->entity_table = \CRM_Financial_BAO_FinancialItem::$_tableName;
    $entityTrxn->entity_id = $financialItem->id;
    $entityTrxn->find(TRUE);

    $entityTrxn->delete();

    $financialItem->delete();
  }

  private static function createAccountingEntries($lineItem, $creditNote, $financialTrxn) {
    $incomeAccount = FinancialAccountUtils::getFinancialTypeAccount($lineItem['financial_type_id'], 'Income Account is');
    $itemParams = [
      'transaction_date' => $creditNote['date'],
      'contact_id' => $creditNote['contact_id'],
      'currency' => $creditNote['currency'],
      'amount' => ($lineItem['quantity'] * $lineItem['unit_price']) * -1,
      'description' => $lineItem['description'],
      'status_id' => 'Unpaid',
      'financial_account_id' => $incomeAccount,
      'entity_table' => \CRM_Financeextras_DAO_CreditNoteLine::$_tableName,
      'entity_id' => $lineItem['id'],
    ];
    \CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $financialTrxn);

    if (!empty($lineItem['tax_amount']) && $lineItem['tax_amount'] > 0) {
      $taxAccount = FinancialAccountUtils::getFinancialTypeAccount($lineItem['financial_type_id'], 'Sales Tax Account is');
      $taxAccountDesc = \Civi\Api4\FinancialAccount::get()
        ->addSelect('description')
        ->addWhere('id', '=', $taxAccount)
        ->execute()
        ->first()['description'] ?? "";

      $itemParams['amount'] = $lineItem['tax_amount'] * -1;
      $itemParams['financial_account_id'] = $taxAccount;
      $itemParams['description'] = $lineItem['description'] . " - " . $taxAccountDesc;
      \CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $financialTrxn);
    }
  }

  /**
   * Calculates the percentage tax amount.
   *
   * E.g. 5% of 10.
   *
   * @param float $percentage
   *   Percentage to calculate.
   * @param float $value
   *   The value to get percentage of.
   *
   * @return float
   *   Calculated Percentage in float
   */
  private static function calculateTaxAmount(float $percentage, float $value) {
    return (floatval($percentage) / 100) * floatval($value);
  }

}
