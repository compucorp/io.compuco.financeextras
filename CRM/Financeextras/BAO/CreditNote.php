<?php

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

}
