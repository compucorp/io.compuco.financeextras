<?php

namespace Civi\Financeextras\APIWrapper;

/**
 * Bridges Search Kit's bulk-download flow with the SOA finance report's non-default row key.
 */
class SearchDisplayDownload {

  /**
   * Listener callback for `civi.api.prepare`.
   *
   * @param mixed $event
   *   Civi api event (Civi\API\Event\PrepareEvent).
   */
  public static function prepare($event) {
    try {
      if ($event->getApiRequestSig() !== '4.searchdisplay.download') {
        return;
      }
      $request = $event->getApiRequest();
      if (!self::isTargetReport($request)) {
        return;
      }
      self::translateSelectedIds($request);
    }
    catch (\Throwable $e) {
      \Civi::log()->warning('SOA finance download filter translation skipped: ' . $e->getMessage());
    }
  }

  /**
   * @param mixed $request
   *   The Api4 action (Civi\Api4\Action\SearchDisplay\Download).
   * @return bool
   */
  private static function isTargetReport($request): bool {
    $params = $request['params'] ?? [];

    $afform = $params['afform'] ?? NULL;
    if (is_string($afform) && $afform === 'afsearchAllocatedPaymentsReport') {
      return TRUE;
    }

    $savedSearch = $params['savedSearch'] ?? NULL;
    if (is_array($savedSearch)) {
      $savedSearch = $savedSearch['name'] ?? NULL;
    }
    if (is_string($savedSearch) && $savedSearch === 'SOA_Finance_Report_Beta_v3') {
      return TRUE;
    }

    $display = $params['display'] ?? NULL;
    if (is_array($display)) {
      $display = $display['name'] ?? NULL;
    }
    if (is_string($display) && $display === 'SOA_Finance_Report_Beta_v3_Table_1') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * If the download was triggered with checkbox-selected rows,
   * translate the `filters.id` array (which holds EntityFinancialTrxn
   * ids because of the SearchDisplayRun key swap) into a pair of
   * filters on FinancialItem.id and the joined FinancialTrxn.id.
   *
   * @param mixed $request
   *   The Api4 action.
   */
  private static function translateSelectedIds($request): void {
    $filters = $request->getFilters() ?? [];
    $selected = $filters['id'] ?? NULL;
    if (empty($selected) || !is_array($selected)) {
      return;
    }

    $rows = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addSelect('entity_id', 'financial_trxn_id')
      ->addWhere('id', 'IN', array_values($selected))
      ->addWhere('entity_table', '=', 'civicrm_financial_item')
      ->execute();

    if (!count($rows)) {
      return;
    }

    $entityIds = [];
    $financialTrxnIds = [];
    foreach ($rows as $row) {
      $entityIds[$row['entity_id']] = $row['entity_id'];
      $financialTrxnIds[$row['financial_trxn_id']] = $row['financial_trxn_id'];
    }

    $filters['id'] = array_values($entityIds);
    $filters['FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.id'] = array_values($financialTrxnIds);
    $request->setFilters($filters);
  }

}
