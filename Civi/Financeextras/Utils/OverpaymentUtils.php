<?php

namespace Civi\Financeextras\Utils;

use Civi\Api4\Contribution;
use Civi\Api4\Company;

/**
 * Utility class for overpayment detection and handling.
 */
class OverpaymentUtils {

  /**
   * Get the overpayment amount for a contribution.
   *
   * Overpayment = Total Paid - Invoice Total (as positive number)
   *
   * @param int $contributionId
   *   The contribution ID.
   *
   * @return float
   *   The overpayment amount (0 if no overpayment).
   */
  public static function getOverpaymentAmount(int $contributionId): float {
    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('total_amount')
      ->execute()
      ->first();

    if (empty($contribution)) {
      return 0;
    }

    $totalPaid = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contributionId, TRUE);

    $overpayment = $totalPaid - $contribution['total_amount'];

    return max(0, (float) $overpayment);
  }

  /**
   * Check if contribution is eligible for overpayment allocation.
   *
   * @param int $contributionId
   *   The contribution ID.
   *
   * @return bool
   *   TRUE if eligible, FALSE otherwise.
   */
  public static function isEligibleForOverpaymentAllocation(int $contributionId): bool {
    // Check setting is enabled.
    if (!\Civi::settings()->get('financeextras_enable_overpayments')) {
      return FALSE;
    }

    // Check contribution status is "Pending refund".
    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addSelect('contribution_status_id:name')
      ->execute()
      ->first();

    if (empty($contribution) || $contribution['contribution_status_id:name'] !== 'Pending refund') {
      return FALSE;
    }

    // Check has overpayment amount.
    return self::getOverpaymentAmount($contributionId) > 0;
  }

  /**
   * Get the company's overpayment financial type.
   *
   * @param int $ownerOrganizationId
   *   The owner organization contact ID.
   *
   * @return int|null
   *   The financial type ID or NULL if not configured.
   */
  public static function getOverpaymentFinancialTypeId(int $ownerOrganizationId): ?int {
    $company = Company::get(FALSE)
      ->addWhere('contact_id', '=', $ownerOrganizationId)
      ->addSelect('overpayment_financial_type_id')
      ->execute()
      ->first();

    return $company['overpayment_financial_type_id'] ?? NULL;
  }

}
