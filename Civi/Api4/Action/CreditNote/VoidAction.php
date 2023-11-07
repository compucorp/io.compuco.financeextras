<?php

namespace Civi\Api4\Action\CreditNote;

use Civi\Api4\CreditNote;
use CRM_Core_Transaction;
use Civi\Api4\CreditNoteLine;
use Civi\Api4\Generic\Result;
use Civi\Api4\CreditNoteAllocation;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;

/**
 * Voids Credit notes.
 */
class VoidAction extends AbstractAction {
  use DAOActionTrait;

  /**
   * credit note id.
   *
   * @var int
   */
  protected $id;

  /**
   * credit note.
   *
   * @var int
   */
  private $creditNote;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    $transaction = CRM_Core_Transaction::create();
    try {
      if (is_int($this->id)) {
        $result[] = $this->voidCreditNote();
      }

      $transaction->commit();
    }
    catch (\Throwable $th) {
      $transaction->rollback();
      throw $th;
    }
  }

  /**
   * Voids credit notes and lines accounting entries.
   *
   * @throws \API_Exception
   */
  private function voidCreditNote(): void {
    if (!$this->validateAction()) {
      throw new \API_Exception("Allocation has been made from the credit note, or doesn't exist");
    }

    $finacialTypeId = $this->creditNote['items'][0]['financial_type_id'];
    $financialTrxn = \CRM_Financeextras_BAO_CreditNote::voidCreditNote($this->id, $finacialTypeId);

    \CRM_Financeextras_BAO_CreditNoteLine::voidAccountingEntries($this->creditNote, $financialTrxn);
  }

  /**
   * Validates if a credit note can be void.
   *
   * Credit notes can be voided if
   *  - status is open
   *  - no amounts have been allocated
   *  - no cash refund recorded
   *  - no credit card refund recorded
   *
   * @return bool
   *   TRUE if credit note can be void, otherwise FALSE.
   */
  private function validateAction() {
    $this->creditNote = CreditNote::get()
      ->addWhere('id', '=', $this->id)
      ->addWhere('status_id:name', '=', 'open')
      ->addChain('items', CreditNoteLine::get()
        ->addWhere('credit_note_id', '=', '$id')
      )
      ->execute()
      ->first();

    if (empty($this->creditNote)) {
      return FALSE;
    }

    $creditNoteAllocations = CreditNoteAllocation::get()
      ->addWhere('credit_note_id', '=', $this->id)
      ->addWhere('is_reversed', '=', FALSE)
      ->execute()
      ->first();

    return empty($creditNoteAllocations);
  }

}
