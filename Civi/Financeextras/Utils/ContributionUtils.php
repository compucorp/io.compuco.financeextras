<?php

namespace Civi\Financeextras\Utils;

class ContributionUtils {

  /**
   * Porportionally allocates the amount being paid to the contribution line items.
   *
   * - Gets the total amount for each line item.
   * - Determines the portion of payment allocated to each line item by assessing the ratio
   *   of the total payment for the entire contribution.
   * - This ratio is then applied to distribute the payment across all line items.
   *
   * @param float $amount
   *  Amount being paid
   *
   * @param int $contributionId
   *  The contribution being paid for
   *
   * @return array
   */
  public static function allocatePaymentToLineItem($amount, $contributionId) {
    $result = [];
    $lineItems = \CRM_Price_BAO_LineItem::getLineItemsByContributionID($contributionId);

    $totalAmount = \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('total_amount')
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->first()['total_amount'] ?? 0;

    if ($totalAmount <= 0) {
      return $result;
    }

    $ratio = (float) $amount / (float) $totalAmount;
    foreach ($lineItems as $id => $lineItem) {
      $result[] = [$id => $lineItem['subTotal'] * $ratio];
    }

    return $result;
  }

}
