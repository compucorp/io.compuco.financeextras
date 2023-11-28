<?php

namespace Civi\Api4\Action\CreditNoteAllocation;

use CRM_Core_Transaction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use CRM_Financeextras_BAO_CreditNoteAllocation;

/**
 * Reverse credit note allocation.
 */
class ReverseAction extends AbstractAction {
  use DAOActionTrait;

  /**
   * Credit Note allocation ID
   *
   * @var int
   */
  protected $id;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    $resultArray = [];
    try {
      $transaction = CRM_Core_Transaction::create();
      $resultArray = $this->reverseAllocation();
    }
    catch (\Throwable $th) {
      \Civi::log()->error('unable to reverse credit note allocation' . $th->getMessage(), ['error' => $th]);
      $transaction->rollback();

      throw $th;
    }

    $transaction->commit();
    return $result->exchangeArray($resultArray);
  }

  /**
   * Reverses credit note allocation.
   *
   * @return array
   *
   */
  private function reverseAllocation() {
    return CRM_Financeextras_BAO_CreditNoteAllocation::reverseWithAccountingEntries($this->id);
  }

}
