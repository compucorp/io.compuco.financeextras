<?php

namespace Civi\Financeextras\Payment;

use Civi\Financeextras\Refund\PaymentProcessor;

/**
 * Refund
 * @package Civi\Financeextras\Refund
 */
class Refund {

  /**
   * @var int
   */
  private $contributionID;

  /**
   * @param $contributionID
   */
  public function __construct($contributionID) {
    $this->contributionID = $contributionID;
  }

  /**
   * Checks if the contribution contains any financial transaction
   * that links to any payment processor with refund enabled.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function isEligibleForRefund(): bool {
    //API is likely to return more than one entity as CiviCRM separates financial
    //transactions for financial transaction for example, contribution amount is record
    //as one financial transaction and fee is record in another transaction.
    $contribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $this->contributionID,
      'return' => ['contribution_status_id'],
    ]);
    if (isset($contribution['contribution_status_id']) && $contribution['contribution_status_id'] == 7) {
      return FALSE;
    }
    $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', [
      'sequential' => 1,
      'entity_id' => $this->contributionID,
      'entity_table' => 'civicrm_contribution',
      'options' => ['limit' => 0],
    ])['values'];
    $supportRefundProcessorIDs = PaymentProcessor::singleton()->getRefundProcessorIDs();
    foreach ($entityFinancialTrxns as $entity) {
      $trxn = civicrm_api3('FinancialTrxn', 'get', [
        'id' => $entity['financial_trxn_id'],
      ]);

      $trxnID = $trxn['id'];
      if (empty($trxnID)) {
        continue;
      }

      if (isset($trxn['values'][$trxnID]['payment_processor_id']) && in_array($trxn['values'][$trxnID]['payment_processor_id'], $supportRefundProcessorIDs)) {
        //Since payment processor that support refund found in one of FinancialTrxn records,
        //return TRUE immediately as the contribution is eligible for refund.
        return TRUE;
      }
    }

    return FALSE;

  }

  /**
   * Checks if current logged-in user has permissions to preform the refund.
   *
   * @return bool
   */
  public function contactHasRefundPermission(): bool {
    $permissions = [
      'access CiviCRM',
      'access CiviContribute',
      'edit contributions',
    ];

    if (!\CRM_Core_Permission::check($permissions)) {
      return FALSE;
    }

    return TRUE;
  }

}
