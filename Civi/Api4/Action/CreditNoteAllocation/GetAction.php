<?php

namespace Civi\Api4\Action\CreditNoteAllocation;

use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\DAOGetAction;
use CRM_Financeextras_BAO_CreditNoteAllocation;

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
        $item['paid_from'] = $this->getPaidfrom($item['id']);
      }

      $result->exchangeArray($items);
    }
  }

  private function getPaidFrom($id) {
    return CRM_Financeextras_BAO_CreditNoteAllocation::getPaidFrom($id);
  }

}
