<?php

namespace Civi\Api4\Action\CreditNote;

use CRM_Core_Transaction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\CreditNote;
use Civi\Api4\Contribution;
use Civi\Api4\Company;
use Civi\Api4\OptionValue;
use Civi\Api4\EntityFinancialAccount;
use Civi\Financeextras\Utils\OverpaymentUtils;
use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;
use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;

/**
 * Allocate overpayment to a new credit note.
 *
 * This action creates a credit note for the overpayment amount and
 * records a negative payment on the contribution to balance it.
 * The credit note remains open with full credit available for allocation
 * to future invoices.
 */
class AllocateOverpaymentAction extends AbstractAction {

  /**
   * The contribution ID with the overpayment.
   *
   * @var int
   */
  protected $contributionId;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    $transaction = CRM_Core_Transaction::create();

    try {
      $creditNote = $this->processOverpaymentAllocation();
      $result->exchangeArray([$creditNote]);
    }
    catch (\Throwable $e) {
      $transaction->rollback();
      throw $e;
    }

    $transaction->commit();
  }

  /**
   * Process the overpayment allocation.
   *
   * @return array
   *   The created credit note.
   *
   * @throws \CRM_Core_Exception
   */
  private function processOverpaymentAllocation(): array {
    $this->validate();

    // Get contribution details with owner organization.
    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->addSelect('*', 'financeextras_contribution_owner.owner_organization')
      ->execute()
      ->first();

    $ownerOrgId = $contribution['financeextras_contribution_owner.owner_organization']
      ?? $this->getDefaultOwnerOrganization();

    if (empty($ownerOrgId)) {
      throw new \CRM_Core_Exception('Could not determine owner organization for the contribution.');
    }

    // Get overpayment financial type from company.
    $financialTypeId = OverpaymentUtils::getOverpaymentFinancialTypeId((int) $ownerOrgId);
    if (empty($financialTypeId)) {
      throw new \CRM_Core_Exception('Overpayment financial type is not configured for the company. Please configure it in the company settings.');
    }

    $overpaymentAmount = OverpaymentUtils::getOverpaymentAmount($this->contributionId);

    // Create credit note (remains open with full credit available).
    $creditNote = $this->createOverpaymentCreditNote(
      (int) $contribution['contact_id'],
      (int) $ownerOrgId,
      (int) $financialTypeId,
      $overpaymentAmount,
      $contribution['currency']
    );

    // Record negative payment on contribution to balance it.
    // This will change the contribution status from "Pending refund" to "Completed".
    $this->recordOverpaymentAdjustment(
      $this->contributionId,
      $overpaymentAmount,
      $creditNote['cn_number'],
      (int) $contribution['financial_type_id']
    );

    return $creditNote;
  }

  /**
   * Validate the request.
   *
   * @throws \CRM_Core_Exception
   */
  private function validate(): void {
    if (empty($this->contributionId)) {
      throw new \CRM_Core_Exception('Contribution ID is required.');
    }

    if (!OverpaymentUtils::isEligibleForOverpaymentAllocation($this->contributionId)) {
      throw new \CRM_Core_Exception('This contribution is not eligible for overpayment allocation.');
    }
  }

  /**
   * Get the default owner organization.
   *
   * @return int|null
   *   The default owner organization contact ID.
   */
  private function getDefaultOwnerOrganization(): ?int {
    $company = Company::get(FALSE)
      ->addSelect('contact_id')
      ->setLimit(1)
      ->execute()
      ->first();

    return $company['contact_id'] ?? NULL;
  }

  /**
   * Create the overpayment credit note.
   *
   * @param int $contactId
   *   The contact ID.
   * @param int $ownerOrgId
   *   The owner organization ID.
   * @param int $financialTypeId
   *   The financial type ID for overpayments.
   * @param float $amount
   *   The overpayment amount.
   * @param string $currency
   *   The currency code.
   *
   * @return array
   *   The created credit note.
   */
  private function createOverpaymentCreditNote(
    int $contactId,
    int $ownerOrgId,
    int $financialTypeId,
    float $amount,
    string $currency
  ): array {
    $today = date('Y-m-d');
    $todayFormatted = \CRM_Utils_Date::customFormat($today, \Civi::settings()->get('dateformatshortdate'));

    // Get the 'open' status ID.
    $openStatusId = OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_status')
      ->addWhere('name', '=', 'open')
      ->execute()
      ->first()['value'] ?? NULL;

    // Get tax rate for the financial type if configured.
    $taxRate = $this->getTaxRateForFinancialType($financialTypeId);

    return CreditNote::save(FALSE)
      ->addRecord([
        'contact_id' => $contactId,
        'owner_organization' => $ownerOrgId,
        'status_id' => $openStatusId,
        'currency' => $currency,
        'date' => $today,
        'description' => ts('Overpayment'),
        'reference' => ts('Contribution #%1', [1 => $this->contributionId]),
        'items' => [
          [
            'description' => ts('Overpayment %1', [1 => $todayFormatted]),
            'financial_type_id' => $financialTypeId,
            'quantity' => 1,
            'unit_price' => $amount,
            'tax_rate' => $taxRate,
          ],
        ],
      ])
      ->execute()
      ->first();
  }

  /**
   * Get the tax rate for a financial type.
   *
   * @param int $financialTypeId
   *   The financial type ID.
   *
   * @return float
   *   The tax rate (0 if no sales tax account is configured).
   */
  private function getTaxRateForFinancialType(int $financialTypeId): float {
    $entityFinancialAccount = EntityFinancialAccount::get(FALSE)
      ->addSelect('financial_account_id.tax_rate')
      ->addWhere('account_relationship:name', '=', 'Sales Tax Account is')
      ->addWhere('entity_table', '=', 'civicrm_financial_type')
      ->addWhere('entity_id', '=', $financialTypeId)
      ->execute()
      ->first();

    return (float) ($entityFinancialAccount['financial_account_id.tax_rate'] ?? 0);
  }

  /**
   * Record overpayment adjustment on the contribution to balance it.
   *
   * This records a negative payment which reduces the paid amount
   * so that the contribution balances (paid_amount = total_amount).
   * The overpayment is not refunded to the customer - it is converted
   * to credit note credit for use on future invoices.
   *
   * Since this is an internal allocation (not actual money movement),
   * both sides of the transaction use the Accounts Receivable account.
   *
   * @param int $contributionId
   *   The contribution ID.
   * @param float $amount
   *   The overpayment amount (will be recorded as negative).
   * @param string $creditNoteNumber
   *   The credit note number for reference.
   * @param int $financialTypeId
   *   The contribution's financial type ID.
   */
  private function recordOverpaymentAdjustment(
    int $contributionId,
    float $amount,
    string $creditNoteNumber,
    int $financialTypeId
  ): void {
    // Use accounts_receivable payment instrument since this is an internal allocation.
    $paymentInstrumentId = OptionValueUtils::getValueForOptionValue(
      'payment_instrument',
      AccountsReceivablePaymentMethod::NAME
    );

    $trxn = \CRM_Financial_BAO_Payment::create([
      'contribution_id' => $contributionId,
      'total_amount' => -$amount,
      'trxn_date' => date('Y-m-d H:i:s'),
      'trxn_id' => $creditNoteNumber,
      'payment_instrument_id' => $paymentInstrumentId,
      'is_send_contribution_notification' => FALSE,
    ]);

    // Get Accounts Receivable account from the contribution's financial type.
    $accountsReceivableId = FinancialAccountUtils::getFinancialTypeAccount(
      $financialTypeId,
      'Accounts Receivable Account is'
    );

    // Update the financial transaction:
    // - Set status to "Completed" (Payment BAO hardcodes "Refunded" for negative amounts)
    // - Set both from/to accounts to Accounts Receivable since this is an
    //   internal allocation, not actual money movement
    $completedStatusId = \CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'status_id', 'Completed');
    \CRM_Core_BAO_FinancialTrxn::create([
      'id' => $trxn->id,
      'status_id' => $completedStatusId,
      'from_financial_account_id' => $accountsReceivableId,
      'to_financial_account_id' => $accountsReceivableId,
    ]);

    // Dispatch event to trigger contribution status recalculation.
    // This is normally done by the Payment API wrapper, but since we're
    // using the BAO directly, we need to dispatch it manually.
    \Civi::dispatcher()->dispatch(
      ContributionPaymentUpdatedEvent::NAME,
      new ContributionPaymentUpdatedEvent($contributionId)
    );
  }

}
