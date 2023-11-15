<?php

namespace Civi\Financeextras\APIWrapper;


class AllocatedPaymentsReport {

  /**
   * Callback to wrap SearchDisplay API calls.
   */
  public static function Respond($event) {
    $request = $event->getApiRequestSig();
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();

    $isAfform = !empty($apiRequest['params']['afform']) && $apiRequest['params']['afform'] == 'afsearchAllocatedPaymentsReport';

    switch ($request) {
      case '4.searchdisplay.getdefault':
        self::addCustomAllocatedPaymentsColumns($result);
        break;

      case '4.searchdisplay.run' && $isAfform:
        self::alterAllocatedPaymentsSearchDisplay($result);
        break;
    }
  }

  /**
   * Adds custom column to the allocated payment search display.
   *
   * @param &$result
   *    The API result object.
   */
  private static function addCustomAllocatedPaymentsColumns(&$result) {
    foreach ($result as &$display) {
      if ($display['label'] === 'SOA Finance Report Beta v3') {
        array_splice(
          $display['settings']['columns'],
          0,
          0,
          [['type' => 'field', 'key' => 'type', 'dataType' => 'String', 'label' => 'Type', 'sortable' => FALSE]]
        );
        array_splice(
          $display['settings']['columns'],
          4,
          0,
          [['type' => 'field', 'key' => 'reference', 'dataType' => 'String', 'label' => 'Reference', 'sortable' => FALSE]]
        );
        array_splice(
          $display['settings']['columns'],
          9,
          0,
          [['type' => 'field', 'key' => 'taxAmount', 'dataType' => 'String', 'label' => 'Tax Amount', 'sortable' => FALSE]]
        );
      }
    }
  }

  /**
   * Alter allocated payments search display result.
   *
   * @param &$result
   *    The API result object.
   */
  private static function alterAllocatedPaymentsSearchDisplay(&$result) {
    foreach ($result as &$display) {
      $display['columns'][0] = [
        'val' => $display['data']['FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.total_amount'] > 0 ? 'BR' : 'BP',
        'label' => 'Type',
      ];
      $display['data']['type'] = $display['data']['FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.total_amount'] > 0 ? 'BR' : 'BP';
      $display['columns'][4] = [
        'val' => 'CiviCRM',
        'label' => 'Reference',
      ];
      $display['data']['reference'] = 'CiviCRM';
      $display['columns'][9] = [
        'val' => 12.5,
        'label' => 'Tax Amount',
      ];
      $display['data']['taxAmount'] = 12.5;
    }
  }

}
