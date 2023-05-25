<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

 namespace Civi\Api4\Action\CreditNote;

use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Generic\Result;

/**
 * Retrieve $ENTITIES based on criteria specified in the `where` parameter.
 *
 * Use the `select` param to determine which fields are returned, defaults to `[*]`.
 *
 * Perform joins on other related entities using a dot notation.
 *
 */
class GetAction extends DAOGetAction {

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  protected function getObjects(Result $result) {
    parent::getObjects($result);

    if ($result->count() > 0) {
      $items = $result->getArrayCopy();

      foreach ($items as &$item) {
        $allocatedCredits = $this->getAllocations($item['id']);
        $item = array_merge($item, $allocatedCredits);
        $item['remaining_credit'] = $item['total_credit'] - array_sum($allocatedCredits);
      }

      $result->exchangeArray($items);
    }
  }

  private function getAllocations($id) {
    $allocations = \Civi\Api4\CreditNoteAllocation::get()
      ->addSelect('*', 'type_id:name')
      ->addWhere('credit_note_id', '=', $id)
      ->execute()
      ->getArrayCopy();

    $allocatedInvoices = array_filter($allocations, fn ($allocation) => $allocation['type_id:name'] == 'invoice');
    $allocatedManualRefunds = array_filter($allocations, fn ($allocation) => $allocation['type_id:name'] == 'manual_refund_payment');
    $allocatedOnlineRefunds = array_filter($allocations, fn ($allocation) => $allocation['type_id:name'] == 'online_refund_payment');

    return [
      'allocated_invoice' => array_sum(array_column($allocatedInvoices, 'amount')),
      'allocated_manual_refund' => array_sum(array_column($allocatedManualRefunds, 'amount')),
      'allocated_online_refund' => array_sum(array_column($allocatedOnlineRefunds, 'amount')),
    ];
  }

}
