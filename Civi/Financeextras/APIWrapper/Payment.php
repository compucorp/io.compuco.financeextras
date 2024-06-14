<?php

namespace Civi\Financeextras\APIWrapper;

use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;

class Payment {

  public static function Prepare($event) {
    try {
      $requestSignature = $event->getApiRequestSig();
      $request          = $event->getApiRequest();

      if ($requestSignature === '3.payment.delete' && !empty($request['params']['id'])) {
        $eftParams = ['entity_table' => 'civicrm_contribution', 'financial_trxn_id' => $request['params']['id'], 'return' => ['entity_id']];
        $entity    = civicrm_api3('EntityFinancialTrxn', 'getsingle', $eftParams);
        if (!empty($entity['entity_id'])) {
          $session = \CRM_Core_Session::singleton();
          $session->set('contributionIdForDeletedPayment', $entity['entity_id']);
        }
      }
    }
    catch (\Throwable $e) {
    }
  }

  public static function Respond($event) {
    try {
      $requestSignature = $event->getApiRequestSig();
      $contributionId   = 0;

      switch ($requestSignature) {
        case '3.payment.create':
          $request        = $event->getApiRequest();
          $contributionId = !empty($request['params']['contribution_id']) ? $request['params']['contribution_id'] : 0;
          break;

        case '3.payment.cancel':
          $request = $event->getApiRequest();
          if (!empty($request['params']['id'])) {
            $eftParams      = ['entity_table' => 'civicrm_contribution', 'financial_trxn_id' => $request['params']['id'], 'return' => ['entity_id']];
            $entity         = civicrm_api3('EntityFinancialTrxn', 'getsingle', $eftParams);
            $contributionId = !empty($entity['entity_id']) ? $entity['entity_id'] : 0;
          }
          break;

        case '3.payment.delete':
          $session        = \CRM_Core_Session::singleton();
          $contributionId = !empty($session->get('contributionIdForDeletedPayment')) ? $session->get('contributionIdForDeletedPayment') : 0;
          $session->set('contributionIdForDeletedPayment', 0);
          break;
      }

      if (!empty($contributionId)) {
        \Civi::dispatcher()->dispatch(
          ContributionPaymentUpdatedEvent::NAME,
          new ContributionPaymentUpdatedEvent($contributionId)
        );
      }
    }
    catch (\Throwable $e) {
    }
  }

}
