<?php

namespace Civi\Financeextras\Payment;

/**
 * RefundTrait
 * @package Civi\Financeextras\Payment
 */
trait RefundTrait {

  /**
   * Checks if given contribution ID contains any eligible financial transactions
   * that link to any payment processors that have supported refund enabled.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  protected function isContributionEligibleToRefund(int $contributionID): bool {
    //API is likely to return more than one entity as CiviCRM separates financial
    //transactions for financial transaction for example, contribution amount is record
    //as one financial transaction and fee is record in another transaction.
    $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', [
      'sequential' => 1,
      'entity_id' => $contributionID,
      'entity_table' => "civicrm_contribution",
    ])['values'];

    $trxnProcessorIDs = [];
    foreach ($entityFinancialTrxns as $entity) {
      $trxn = civicrm_api3('FinancialTrxn', 'getsingle', [
        'id' => $entity['financial_trxn_id'],
      ])['payment_processor_id'];

      if (!empty($trxn)) {
        $trxnProcessorIDs[] = $trxn;
      }
    }

    if (empty($trxnProcessorIDs)) {
      return FALSE;
    }

    $supportRefundProcessorIDs = PaymentProcessor::singleton()->getRefundProcessorIDs();
    foreach ($trxnProcessorIDs as $trxnProcessorID) {
      if (in_array($trxnProcessorID, $supportRefundProcessorIDs)) {
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
  protected function hasRefundPermission(): bool {
    $permissions = [
      'access CiviCRM backend and API',
      'access CiviContribute',
      'edit contributions',
    ];

    if (!\CRM_Core_Permission::check($permissions)) {
      return FALSE;
    }

    return TRUE;
  }

}
