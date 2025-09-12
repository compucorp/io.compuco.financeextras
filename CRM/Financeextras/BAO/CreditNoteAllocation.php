<?php

use Civi\Api4\CreditNoteAllocation;
use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;
use Civi\Financeextras\Utils\ContributionUtils;

class CRM_Financeextras_BAO_CreditNoteAllocation extends CRM_Financeextras_DAO_CreditNoteAllocation {

  /**
   * Create a new CreditNoteAllocation based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_CreditNoteAllocation|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_CreditNoteAllocation';
    $entityName = 'CreditNoteAllocation';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Creates and Records the credit note allocation as payment.
   *
   * @param array $data
   *  Credit note allocation data.
   *
   * @return array
   *   New credit note created object as array
   *
   */
  public static function createWithAccountingEntries($data) {
    $transaction = CRM_Core_Transaction::create();
    try {
      $allocation = self::create($data)->toArray();

      self::createAccountingEntries($allocation, $data['credit_note_id'], $data['contribution_id'], $data['amount']);

      if (!empty($allocation['contribution_id'])) {
        \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($allocation['contribution_id']));
      }
    }
    catch (\Throwable $th) {
      $transaction->rollback();

      throw $th;
    }

    return $allocation;
  }

  /**
   * Reverses Credit note allocation and creates accounting entries.
   *
   * As allocating credit note is synonnymous to creating a payment,
   * reversing an allocation is synonymous to refunding a payment (i.e. payment with negative amount)
   *
   * @param int $id
   *   Credit note allocation ID.
   */
  public static function reverseWithAccountingEntries(int $id) {
    $allocation = self::getCreditNoteAllocationById($id);
    if (empty($allocation)) {
      throw new \CRM_Core_Exception("Credit Note allocation not found");
    }

    $reference = $allocation['reference'] ?? NULL;
    self::create([
      'id' => $allocation['id'],
      'is_reversed' => TRUE,
    ]);
    $account = FinancialAccountUtils::getFinancialTypeAccount($allocation['line'][0]['financial_type_id'], 'Accounts Receivable Account is');

    $amount = -$allocation['amount'];

    $params = [
      'contribution_id' => $allocation['contribution_id'],
      'total_amount' => $amount,
      'trxn_date' => $allocation['date'],
      'trxn_id' => $reference,
    ];
    $transaction = self::createPayment($account, $params);
    self::createAllocationEntityTransactions((int) $allocation['id'], (int) $transaction->id, (int) $amount);

    // Create allocation transaction with positive amount for reversal
    $allocationAmount = $allocation['amount'];
    $allocationTransaction = self::createAllocationTransaction($account, (int) $allocation['contribution_id'], (float) $allocationAmount, $allocation['date'], $reference);
    self::createAllocationEntityTransactions((int) $allocation['id'], (int) $allocationTransaction->id, (float) $allocationAmount);

    if (!empty($allocation['contribution_id'])) {
      \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($allocation['contribution_id']));
    }

    return $allocation;
  }

  /**
   * Deletes credit note allocation accounting entries.
   *
   * @param int $creditNoteId
   *  The credit note unique identifier.
   */
  public static function deleteAccountingEntries($creditNoteId): void {
    $allocations = CreditNoteAllocation::get(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute();

    foreach ($allocations as $allocation) {
      $entityTrxn = new \CRM_Financial_DAO_EntityFinancialTrxn();
      $entityTrxn->entity_table = self::$_tableName;
      $entityTrxn->entity_id = $allocation['id'];
      $entityTrxn->find();

      while ($entityTrxn->fetch()) {
        if (!empty($entityTrxn->financial_trxn_id)) {
          $trxn = new \CRM_Financial_DAO_FinancialTrxn();
          $trxn->id = $entityTrxn->financial_trxn_id;
          $trxn->delete();
        }
      }
      $entityTrxn->delete();

      if (!empty($allocation['contribution_id'])) {
        \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($allocation['contribution_id']));
      }
    }

    CreditNoteAllocation::delete(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute();
  }

  /**
   * Creates the neccessary accounting entries using Payment API.
   *
   * @param array $allocation
   *  The credit note allocation
   *
   * @param int $creditNoteId
   *  The credit note credit is to be allocated from.
   *
   * @param int $contributionId
   *  Unique identifier of the contribtuion to allocated credit to.
   *
   * @param float $amount
   *  The amount to be allocated.
   */
  private static function createAccountingEntries($allocation, $creditNoteId, $contributionId, $amount) {
    $date = date("Y-m-d H:i:s");
    $reference = $allocation['reference'] ?? NULL;

    $params = [
      'contribution_id' => $contributionId,
      'total_amount' => $amount,
      'trxn_date' => $date,
      'trxn_id' => $reference,
    ];

    $creditNoteLine = \Civi\Api4\CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id.id', '=', $creditNoteId)
      ->execute()
      ->first();

    $account = FinancialAccountUtils::getFinancialTypeAccount(
      $creditNoteLine['financial_type_id'],
      'Accounts Receivable Account is'
    );

    // Create the original payment transaction
    $transaction = self::createPayment($account, $params);
    self::createAllocationEntityTransactions((int) $allocation['id'], $transaction->id, $amount);

    // Create allocation transaction with negative amount for allocation
    $allocationAmount = -$amount;
    $allocationTransaction = self::createAllocationTransaction($account, (int) $contributionId, (float) $allocationAmount, $date, $reference);
    self::createAllocationEntityTransactions((int) $allocation['id'], (int) $allocationTransaction->id, (float) $allocationAmount);
  }

  /**
   * Records an allocationas a contribution payment
   *
   * @param string $account
   *  Financial account the payment will be record payment to and from
   * @param array $paymentParams
   *   Data to pass to the Payment API
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   */
  private static function createPayment($account, $paymentParams) {
    $params = array_merge([
      'is_send_contribution_notification' => FALSE,
      'payment_processor_id' => NULL,
      'payment_instrument_id' => OptionValueUtils::getValueForOptionValue('payment_instrument', 'credit_note'),
    ], $paymentParams);

    if (self::isContributionStatus($params['contribution_id'], 'Completed')) {
      $params['line_item'] = ContributionUtils::allocatePaymentToLineItem($params['total_amount'], $params['contribution_id']);
    }

    self::preventExtendingMembershipEndDate((int) $params['contribution_id']);

    $transaction = \CRM_Financial_BAO_Payment::create($params);

    // The Payment API typically uses the "Accounts Receivable" as the "from" account
    // and the financial account linked to the payment processor or the default
    // asset account as the "to" account. Here, we manually set both accounts
    // to the specified account ID, to ensure they are attached to epxected accounts.
    \CRM_Core_BAO_FinancialTrxn::create([
      'id' => $transaction->id,
      'from_financial_account_id' => $account,
      'to_financial_account_id' => $account,
    ]);

    return $transaction;
  }

  /**
   * Creates a non-payment financial transaction.
   *
   * @param int $account
   *   Financial account ID
   * @param int $contributionId
   *   Contribution ID
   * @param float $amount
   *   Transaction amount
   * @param string $date
   *   Transaction date
   * @param string|null $reference
   *   Allocation reference
   *
   * @return \CRM_Financial_DAO_FinancialTrxn
   */
  private static function createAllocationTransaction(int $account, int $contributionId, float $amount, string $date, ?string $reference = NULL): CRM_Financial_DAO_FinancialTrxn {
    $params = [
      'from_financial_account_id' => $account,
      'to_financial_account_id' => $account,
      'total_amount' => $amount,
      'net_amount' => $amount,
      'currency' => \CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'currency'),
      'trxn_date' => $date,
      'status_id' => \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'payment_instrument_id' => OptionValueUtils::getValueForOptionValue('payment_instrument', 'credit_note'),
      'is_payment' => 1,
      'trxn_id' => $reference,
    ];

    $transaction = new \CRM_Financial_DAO_FinancialTrxn();
    $transaction->copyValues($params);
    $transaction->save();

    return $transaction;
  }

  /**
   * Links allocation to financial transaction.
   *
   * @param int $allocationId
   *   The allocation ID.
   * @param int $transactionId
   *   The transaction ID.
   * @param float $amount
   *   The amount of the allocation.
   */
  public static function createAllocationEntityTransactions($allocationId, $transactionId, $amount) {
    $allocationEntityTrxn = [
      'entity_table' => CRM_Financeextras_BAO_CreditNoteAllocation::$_tableName,
      'financial_trxn_id' => $transactionId,
      'entity_id' => $allocationId,
      'amount' => $amount,
    ];

    $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->copyValues($allocationEntityTrxn);
    $entityTrxn->save();
  }

  /**
   * Returns Debit account of the financial transaction the credit allocation record is linked to.
   *
   * @param int $id
   *  Credit note allocation ID
   * @return string
   *   Debit Account name
   */
  public static function getPaidFrom($id) {
    $entityTrxn = new \CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->entity_table = \CRM_Financeextras_DAO_CreditNoteAllocation::$_tableName;
    $entityTrxn->entity_id = $id;
    $entityTrxn->orderBy('id DESC');
    $entityTrxn->limit(1);
    $entityTrxn->find(TRUE);
    if (empty($entityTrxn->financial_trxn_id)) {
      return NULL;
    }

    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $entityTrxn->financial_trxn_id)
      ->addSelect('to_financial_account_id:label')
      ->execute()
      ->first();

    if (empty($financialTrxn)) {
      return NULL;
    }
    return $financialTrxn['to_financial_account_id:label'];
  }

  /**
   *
   * Retrieves credit note allocation by ID.
   *
   * @param int $id
   * Credit note allocation ID.
   *
   * @return array
   *   Credit note allocation data.
   *
   */
  private static function getCreditNoteAllocationById(int $id): array {
    return \Civi\Api4\CreditNoteAllocation::get(FALSE)
      ->addWhere('id', '=', $id)
      ->addSelect('*', 'credit_note_id.contact_id')
      ->addChain('line', \Civi\Api4\CreditNoteLine::get(FALSE)
        ->addWhere('credit_note_id', '=', '$credit_note_id')
        )
      ->execute()
      ->first();
  }

  /**
   * Checks if Contribution status has the specified status.
   *
   * @param int $contributionId
   *  The contribution ID
   * @param string $status
   *  The status to check against the contribution
   *
   * @return bool
   */
  private static function isContributionStatus($contributionId, $status) {
    return !empty(\Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionId)
      ->addWhere('contribution_status_id:name', '=', $status)
      ->execute()
      ->first());
  }

  private static function preventExtendingMembershipEndDate(int $contributionId): void {
    $manager = \CRM_Extension_System::singleton()->getManager();
    if ($manager->getStatus('uk.co.compucorp.membershipextras') !== \CRM_Extension_Manager::STATUS_INSTALLED) {
      return;
    }

    Civi::$statics[CRM_Financeextras_ExtensionUtil::LONG_NAME]['creditNoteContributionId'] = $contributionId;
  }

}
