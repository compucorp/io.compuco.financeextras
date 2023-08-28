<?php

namespace Civi\Api4\Action\CreditNote;

use CRM_Core_Transaction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;

/**
 * Refund Credit notes.
 */
class RefundAction extends AbstractAction {
  use DAOActionTrait;

  /**
   * credit note ID.
   *
   * @var int
   */
  protected $id;

  /**
   * Refund Amount
   *
   * @var mixed
   */
  protected $amount;

  /**
   * Refund reference
   *
   * @var string
   */
  protected $reference;

  /**
   * Refund Date
   *
   * @var string
   */
  protected $date;

  /**
   * Payment Information
   *
   * @var array
   */
  protected $paymentParam;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    $transaction = CRM_Core_Transaction::create();
    try {
      $allocationParam = [
        'amount' => $this->amount,
        'date' => $this->date,
        'reference' => $this->reference,
      ];
      $result[] = CreditNoteBAO::refund($this->id, $allocationParam, $this->paymentParam);
    }
    catch (\Throwable $th) {
      $transaction->rollback();

      throw $th;
    }
  }

}
