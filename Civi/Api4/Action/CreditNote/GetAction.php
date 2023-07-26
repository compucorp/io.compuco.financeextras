<?php

namespace Civi\Api4\Action\CreditNote;

use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Generic\Result;

/**
 * {@inheritDoc}
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
        if (empty($item['id'])) {
          continue;
        }

        $allocatedCredits = $this->getAllocations($item['id']);
        $item = array_merge($item, $allocatedCredits);

        if (!empty($item['total_credit'])) {
          $item['remaining_credit'] = $item['total_credit'] - array_sum($allocatedCredits);
        }
      }

      $result->exchangeArray($items);
    }
  }

  private function getAllocations($id) {
    $allocations = \Civi\Api4\CreditNoteAllocation::get()
      ->addSelect('*', 'type_id:name')
      ->addWhere('credit_note_id', '=', $id)
      ->addWhere('is_reversed', '=', FALSE)
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
