<?php

namespace Civi\Api4\Action\CreditNote;

use Civi\Api4\CreditNoteLine;
use Civi\Api4\Generic\DAODeleteAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use CRM_Core_Transaction;

/**
 * Delete Credit notes with associated items.
 */
class DeleteWithItemsAction extends DAODeleteAction {
  use DAOActionTrait;

  /**
   * {@inheritDoc}
   */
  protected function deleteObjects($items): array {
    $transaction = CRM_Core_Transaction::create();

    try {
      foreach ($items as $item) {
        $this->deleteAccountingEntries($item['id']);
      }

      return parent::deleteObjects($items);
    }
    catch (\Throwable $th) {
      $transaction->rollback();

      throw $th;
    }
  }

  /**
   * Deletes credit notes and lines accounting entries.
   *
   * @param int $creditNoteId
   *  The credit note unique identifier.
   */
  private function deleteAccountingEntries($creditNoteId): void {
    $creditNoteLines = CreditNoteLine::get()
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute();

    \CRM_Financeextras_BAO_CreditNote::deleteAccountingEntries($creditNoteId);

    foreach ($creditNoteLines as $creditNoteLine) {
      \CRM_Financeextras_BAO_CreditNoteLine::deleteAccountingEntries($creditNoteLine['id']);
    }
  }

}
