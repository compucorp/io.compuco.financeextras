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

    $isAfform = !empty($apiRequest['params']['afform']) && $apiRequest['params']['afform'] == 'afsearchCreditNotes';

    switch ($request) {
      case '4.searchdisplay.get':
        self::addCustomCreditNoteColumns($result);
        break;

      case '4.searchdisplay.run' && $isAfform:
        self::alterCreditNoteSearchDisplay($result);
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
      $creditNote = \Civi\Api4\CreditNote::get()
        ->addSelect('status_id:name', 'total_credit')
        ->addWhere('id', '=', $display['data']['id'])
        ->execute()
        ->first();

      $remaining = $display['data']['total_credit'] - $display['data']['SUM_CreditNote_CreditNoteAllocation_credit_note_id_01_amount'] ?? 0;
      $lastIndex = count($display['columns']) - 1;
      $display['columns'][$lastIndex + 1] = $display['columns'][$lastIndex];
      $display['columns'][$lastIndex] = [
        'val' => \CRM_Utils_Money::format($remaining),
        'label' => 'Remaining',
      ];
      $display['data']['remaining'] = \CRM_Utils_Money::format($remaining);

      self::alterCreditNoteSearchDisplayLinks($creditNote, $display['columns'][$lastIndex + 1]);
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

}
