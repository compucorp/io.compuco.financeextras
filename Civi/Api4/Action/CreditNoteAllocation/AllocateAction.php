<?php

namespace Civi\Api4\Action\CreditNoteAllocation;

use CRM_Core_Transaction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use CRM_Financeextras_BAO_CreditNoteAllocation;

/**
 * Allocate credit to contributions.
 */
class AllocateAction extends AbstractAction {
  use DAOActionTrait;

  /**
   * @var int
   */
  protected $contributionId;

  /**
   * @var int
   */
  protected $creditNoteId;

  /**
   * Allocation reference
   *
   * @var string
   */
  protected $reference;

  /**
   * Allocateion type
   *
   * @var int
   */
  protected $typeId;

  /**
   * Amount to allocate
   *
   * @var int
   */
  protected $amount;

  /**
   * Credit note currecny
   *
   * @var string
   */
  protected $currency;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    $resultArray = [];
    try {
      $transaction = CRM_Core_Transaction::create();
      $resultArray = $this->allocateCredit();
    }
    catch (\Throwable $th) {
      \Civi::log()->error('unable to create allocation ' . $th->getMessage(), ['error' => $th]);
      $transaction->rollback();

      throw $th;
    }

    $transaction->commit();
    return $result->exchangeArray($resultArray);
  }

  /**
   * Allocate credit to contribution.
   *
   * @return array
   *
   */
  private function allocateCredit() {
    $data = [
      'credit_note_id' => $this->creditNoteId,
      'contribution_id' => $this->contributionId,
      'type_id' => $this->typeId,
      'currency' => $this->currency,
      'reference' => $this->reference,
      'amount' => $this->amount,
    ];

    return CRM_Financeextras_BAO_CreditNoteAllocation::createWithAccountingEntries($data);
  }

}
