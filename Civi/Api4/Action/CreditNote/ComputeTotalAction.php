<?php

namespace Civi\Api4\Action\CreditNote;

use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;

/**
 * Computes the total of a credit note.
 */
class ComputeTotalAction extends AbstractAction {
  use DAOActionTrait;

  /**
   * credit note line items.
   *
   * @var array
   */
  protected $lineItems;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    if (is_array($this->lineItems)) {
      $result[] = CreditNoteBAO::computeTotalAmount($this->lineItems);
    }
  }

}
