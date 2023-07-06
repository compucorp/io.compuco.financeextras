<?php

namespace Civi\Financeextras\APIWrapper;

class Contribution {

  /**
   * Callback to wrap Contribution API calls.
   */
  public static function Respond($event) {
    $request = $event->getApiRequestSig();
    $result = $event->getResponse();

    switch ($request) {
      case '4.contribution.get':
        self::addPaidAmountColumn($result);
        break;
    }
  }

  /**
   * Adds amount paid column to Contribution result.
   *
   * @param &$result
   *    The API result object.
   */
  private static function addPaidAmountColumn(&$result) {
    foreach ($result as &$contribution) {
      $contribution['paid_amount'] = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE);
    }
  }

}
