<?php

namespace Civi\Api4\Action\CreditNoteAllocation;

use CRM_Core_Transaction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;

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
      \Civi::log()->error('unable to create allocation ' . $th->getMessage(), $th);
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
    $allocation = \Civi\Api4\CreditNoteAllocation::create()
      ->addValue('credit_note_id', $this->creditNoteId)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('type_id', $this->typeId)
      ->addValue('currency:name', $this->currency)
      ->addValue('reference', $this->reference)
      ->addValue('amount', $this->amount)
      ->execute()
      ->first();

    $this->createAccountingEntries();

    return $allocation;
  }

  /**
   * Record the credit note allocation as payment.
   *
   * This will create the neccessary accounting entries
   * using the Payment class.
   */
  private function createAccountingEntries() {
    $date = date("Y-m-d");
    $params = [
      'contribution_id' => $this->contributionId,
      'total_amount' => $this->amount,
      'trxn_date' => $date,
    ];

    $account = $this->getFinancialAccount();
    $transaction = \CRM_Financial_BAO_Payment::create($params);
    \CRM_Core_BAO_FinancialTrxn::create([
      'id' => $transaction->id,
      'from_financial_account_id' => $account,
      'to_financial_account_id' => $account,
      'payment_processor_id' => NULL,
    ]);
  }

  /**
   * Returns Credit Note Financial Account
   *
   * i.e. Accounts Receivable account for the
   * financial type of the first line of the credit note.
   *
   * @return int|null
   *   Accounts Receivable ID
   */
  private function getFinancialAccount() {
    $creditNoteFirstLine = \Civi\Api4\CreditNoteLine::get()
      ->addSelect('financial_type_id')
      ->addWhere('credit_note_id.id', '=', $this->creditNoteId)
      ->setLimit(1)
      ->execute()
      ->first();

    return \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
      $creditNoteFirstLine['financial_type_id'],
      'Accounts Receivable Account is'
    );
  }

}
