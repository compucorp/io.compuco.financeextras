<?php

namespace Civi\Financeextras\Utils;

class FinancialAccountUtils {

  /**
   * Returns the Financial Account for a Financial Type By Name
   *
   * @param int $financialTypeId
   *  The Financial Type to get account for.
   *
   * @param string $accountName
   *  The name of the account to be retrieved
   *
   * @return int
   *   The account ID
   */
  public static function getFinancialTypeAccount($financialTypeId, $accountName) {
    return \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $financialTypeId,
      $accountName
    );
  }

}
