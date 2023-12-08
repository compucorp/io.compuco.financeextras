<?php

namespace Civi\Financeextras\APIWrapper;

class SearchDisplayRun {

  /**
   * Callback to wrap SearchDisplay API calls.
   */
  public static function Respond($event) {
    $request = $event->getApiRequestSig();
    $apiRequest = $event->getApiRequest();
    $result = $event->getResponse();

    $isAfform = !empty($apiRequest['params']['afform']);
    $isCreditNoteAfform = $isAfform && $apiRequest['params']['afform'] == 'afsearchCreditNotes';
    $isFinanceReportAfform = $isAfform && $apiRequest['params']['afform'] == 'afsearchAllocatedPaymentsReport';

    switch ($request) {
      case '4.searchdisplay.get':
        self::addCustomCreditNoteColumns($result);
        break;

      case '4.searchdisplay.run' && $isCreditNoteAfform:
        self::alterCreditNoteSearchDisplay($result);
        break;

      case '4.searchdisplay.run' && $isFinanceReportAfform:
        self::alterFinanceReportDisplay($result);
        break;
    }
  }

  /**
   * Adds custom column to the credit note search display.
   *
   * @param &$result
   *    The API result object.
   */
  private static function addCustomCreditNoteColumns(&$result) {
    foreach ($result as &$display) {
      if ($display['saved_search_id.api_entity'] == 'CreditNote') {
        $lastIndex = count($display['settings']['columns']) - 1;
        $display['settings']['columns'][$lastIndex + 1] = $display['settings']['columns'][$lastIndex];
        $display['settings']['columns'][$lastIndex] = [
          'type' => 'field',
          'key' => 'remaining',
          'dataType' => 'Money',
          'label' => 'Remaining',
          'sortable' => FALSE,
        ];
      }
    }
  }

  /**
   * Alter credit note search display result.
   *
   * - Compute the value of the custom 'remaining' column.
   * - Add css class to designated links that should be disabled.
   *
   * @param &$result
   *    The API result object.
   */
  private static function alterCreditNoteSearchDisplay(&$result) {
    foreach ($result as &$display) {
      $creditNote = \Civi\Api4\CreditNote::get(FALSE)
        ->addSelect('status_id:name', 'total_credit', 'currency')
        ->addWhere('id', '=', $display['data']['id'])
        ->execute()
        ->first();

      $remaining = $creditNote['remaining_credit'];
      $lastIndex = count($display['columns']) - 1;
      $display['columns'][$lastIndex + 1] = $display['columns'][$lastIndex];
      $display['columns'][$lastIndex] = [
        'val' => \CRM_Utils_Money::format($remaining, $creditNote['currency']),
        'label' => 'Remaining',
      ];
      $display['data']['remaining'] = \CRM_Utils_Money::format($remaining, $creditNote['currency']);

      self::alterCreditNoteSearchDisplayLinks($creditNote, $display['columns'][$lastIndex + 1]);
      self::alterCreditNoteSearchDisplayMoneyColumn($creditNote, $display['columns']);
    }
  }

  /**
   * Adds css class to designated links that should be disabled.
   *
   * @param array $creditNote
   *  Credit note data.
   * @param array &$linkColumn
   *  The column containing the links.
   */
  private static function alterCreditNoteSearchDisplayLinks(array $creditNote, array &$linkColumn) {
    foreach ($linkColumn['links'] as &$link) {
      if ($creditNote['status_id:name'] == 'void' && !in_array($link['text'], ['View', 'Edit', 'Delete'])) {
        $link['style'] = 'disabled';
      }

      $allocatedTotal = $creditNote['allocated_manual_refund'] + $creditNote['allocated_online_refund'] + $creditNote['allocated_invoice'];
      if (!empty($allocatedTotal) && $creditNote['status_id:name'] == 'open' && $link['text'] == 'Void') {
        $link['style'] = 'disabled';
      }

      if ($creditNote['status_id:name'] == 'fully_allocated' && !in_array($link['text'], ['View', 'Edit', 'Delete', 'Download PDF Document Credit Note', 'Email Credit Note'])) {
        $link['style'] = 'disabled';
      }
    }
  }

  /**
   * Formats the money columns using the right currency
   *
   * This is needed because the Core by default formats money datatype column using the system configured format
   *
   * @param array $creditNote
   *  Credit note data.
   * @param array $columns
   *  Credit note search display columns.
   */
  private static function alterCreditNoteSearchDisplayMoneyColumn(array $creditNote, array &$columns) {
    foreach ($columns as &$column) {
      if ($column['label'] == 'Total Value') {
        $column['val'] = \CRM_Utils_Money::format($creditNote['total_credit'], $creditNote['currency']);
      }

      $allocatedTotal = $creditNote['allocated_manual_refund'] + $creditNote['allocated_online_refund'] + $creditNote['allocated_invoice'];
      if ($column['label'] == 'Allocated') {
        $column['val'] = \CRM_Utils_Money::format($allocatedTotal, $creditNote['currency']);
      }
    }
  }

  /**
   * Alter finance report search display result.
   *
   * @param &$result
   *    The API result object.
   */
  private static function alterFinanceReportDisplay(&$result) {
    foreach ($result as &$display) {
      foreach ($display['columns'] as &$column) {
        if (in_array($column['label'], ['Net Amount', 'Tax Amount'])) {
          $column['val'] = \CRM_Utils_Money::format(abs(trim($column['val']) ?: 0));
        }
      }
    }
  }

}
