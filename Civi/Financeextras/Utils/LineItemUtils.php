<?php

namespace Civi\Financeextras\Utils;

use CRM_Core_Exception;

/**
 * Utility class for line item operations across all payment processors
 *
 * Provides generic line item fetching and formatting that works
 * with Stripe, GoCardless, ITAS, and other payment processors.
 */
class LineItemUtils {

  /**
   * Fetch line items from CiviCRM contribution in standardized format
   *
   * Returns line items with tax-inclusive unit prices, suitable for
   * conversion to processor-specific formats (Stripe price_data, GoCardless amounts, etc.)
   *
   * @param int $contributionId CiviCRM contribution ID
   * @return array [{label, unit_price_with_tax, qty, line_total, tax_amount}]
   * @throws CRM_Core_Exception If contribution or line items cannot be fetched
   */
  public static function fetchFromContribution(int $contributionId): array {
    $lineItems = [];

    try {
      $civiLineItems = civicrm_api3('LineItem', 'get', [
        'contribution_id' => $contributionId,
        'sequential' => 1,
      ]);

      foreach ($civiLineItems['values'] as $lineItem) {
        $lineTotal = (float) ($lineItem['line_total'] ?? 0);
        $taxAmount = (float) ($lineItem['tax_amount'] ?? 0);
        $qty = (int) ($lineItem['qty'] ?? 1);

        // Calculate unit price including tax
        // This ensures payment processors charge the full amount
        $unitPriceWithTax = $qty > 0 ? ($lineTotal + $taxAmount) / $qty : 0;

        $lineItems[] = [
          'label' => $lineItem['label'] ?? 'Item',
          'unit_price_with_tax' => $unitPriceWithTax,
          'qty' => $qty,
          'line_total' => $lineTotal,
          'tax_amount' => $taxAmount,
        ];
      }

      // Fallback: If no line items found, create single item from contribution total
      if (empty($lineItems)) {
        $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $contributionId]);
        $totalAmount = (float) ($contribution['total_amount'] ?? 0);

        $lineItems[] = [
          'label' => 'Contribution',
          'unit_price_with_tax' => $totalAmount,
          'qty' => 1,
          'line_total' => $totalAmount,
          'tax_amount' => 0,
        ];
      }

      return $lineItems;

    }
    catch (\CiviCRM_API3_Exception $e) {
      throw new CRM_Core_Exception('Failed to fetch line items for contribution ' . $contributionId . ': ' . $e->getMessage());
    }
  }

  /**
   * Calculate total amount from line items (including tax)
   *
   * @param array $lineItems Line items from fetchFromContribution()
   * @return float Total amount
   */
  public static function calculateTotal(array $lineItems): float {
    $total = 0;

    foreach ($lineItems as $item) {
      $total += $item['unit_price_with_tax'] * $item['qty'];
    }

    return $total;
  }

  /**
   * Validate line items match contribution total
   *
   * @param array $lineItems Line items from fetchFromContribution()
   * @param float $expectedTotal Expected total from contribution
   * @param float $tolerance Allowed difference (default 0.01 for rounding)
   * @return bool True if totals match within tolerance
   */
  public static function validateTotal(array $lineItems, float $expectedTotal, float $tolerance = 0.01): bool {
    $calculatedTotal = self::calculateTotal($lineItems);
    return abs($calculatedTotal - $expectedTotal) <= $tolerance;
  }

}
