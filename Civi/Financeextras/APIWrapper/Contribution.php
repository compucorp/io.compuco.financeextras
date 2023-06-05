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
        self::addAmountDueColumn($result);
        break;
    }
  }

  /**
   * Adds amount due column to Contribution result.
   *
   * @param &$result
   *    The API result object.
   */
  private static function addAmountDueColumn(&$result) {
    foreach ($result as &$contribution) {
      $paidAmount = \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE);
      $contribution['due_amount'] = \CRM_Utils_Money::subtractCurrencies(
        $contribution['total_amount'],
        $paidAmount,
        $contribution['currency']
      );
    }
  }

}
