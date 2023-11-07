<?php

namespace Civi\Financeextras\APIWrapper;

class BatchListPage {

  /**
   * Callback precedes batch.get and batch.getcount
   * API calls but only ony the financial batches list page.
   *
   * See: https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_apiWrappers/#migrating-away-from-this-hook
   */
  public static function preApiCall($event) {
    // make sure this only runs on the financial batches list page.
    $referer = \CRM_Utils_System::refererPath();
    if (strpos($referer, 'civicrm/financial/financialbatches') === FALSE) {
      return;
    }

    // make sure this only runs on batch.get and batch.getcount APIs
    $apiRequestSig = $event->getApiRequestSig();
    if (!in_array($apiRequestSig, ['3.batch.get', '3.batch.getcount'])) {
      return;
    }

    $event->wrapAPI(['Civi\Financeextras\APIWrapper\BatchListPage', 'enforcePermissionCheck']);
  }

  /**
   * Enforces permission check on batch.get
   * and batch.getcount API calls.
   * This is needed because otherwise we won't
   * be able to use hook_civicrm_selectWhereClause that
   * we use to do filter batches list based on the
   * owner organisations filter.
   * See: https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_selectWhereClause/#notes
   *
   * @param array $apiRequest
   * @param array $callsame
   */
  public static function enforcePermissionCheck(&$apiRequest, $callsame) {
    $apiRequest['params']['check_permissions'] = 1;

    return $callsame($apiRequest);
  }

}
