<?php

use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Utils\FinancialAccountUtils;
use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;
use CRM_Financeextras_BAO_CreditNoteAllocation as CreditNoteAllocationBAO;
use Civi\Financeextras\Setup\Manage\CreditNoteStatusManager as CreditNoteStatus;

class CRM_Financeextras_BAO_CreditNote extends CRM_Financeextras_DAO_CreditNote {

  /**
   * Create a new CreditNote based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_CreditNote|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_CreditNote';
    $entityName = 'CreditNote';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);

    if (empty($params['cn_number']) && $hook == 'create') {
      $params['cn_number'] = self::generateCreditNoteNumber($params['owner_organization']);
    }

    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();

    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  private static function generateCreditNoteNumber($ownerOrganization) {
    $companyRecord = \CRM_Core_DAO::executeQuery("SELECT creditnote_prefix, next_creditnote_number FROM financeextras_company WHERE contact_id = {$ownerOrganization} FOR UPDATE");
    $companyRecord->fetch();

    $creditNoteNumber = $companyRecord->next_creditnote_number;
    if (!empty($companyRecord->creditnote_prefix)) {
      $creditNoteNumber = $companyRecord->creditnote_prefix . $companyRecord->next_creditnote_number;
    }

    $creditUpdateSQLFormula = self::getCreditNoteNumberSQLUpdateFormula($companyRecord->next_creditnote_number);
    \CRM_Core_DAO::executeQuery("UPDATE financeextras_company SET next_creditnote_number = {$creditUpdateSQLFormula}  WHERE contact_id = {$ownerOrganization}");

    return $creditNoteNumber;
  }

  /**
   * Gets the SQL formula to update the credit
   * number, where if the next credit note number
   * starts with a zero, then it means it has a leading zero(s)
   * and thus they should be respected, or otherwise
   * the credit note number would be incremented normally.
   *
   * @param string $creditNumberNumericPart
   *
   * @return string
   */
  private static function getCreditNoteNumberSQLUpdateFormula($creditNumberNumericPart) {
    $firstZeroLocation = strpos($creditNumberNumericPart, '0');
    $isThereLeadingZero = $firstZeroLocation === 0;
    if ($isThereLeadingZero) {
      $creditNumberCharCount = strlen($creditNumberNumericPart);
      $creditUpdateFormula = "LPAD((next_creditnote_number + 1), {$creditNumberCharCount}, '0')";
    }
    else {
      $creditUpdateFormula = "(next_creditnote_number + 1)";
    }

    return $creditUpdateFormula;
  }

  /**
   * Computes the credit note line item total.
   *
   * @param array $items
   *   Array of credit note line items.
   *
   * @return array
   *   ['totalAfterTax' => <value>, 'totalBeforeTax' => <value>]
   */
  public static function computeTotalAmount(array $items) {
    $totalBeforeTax = round(array_reduce($items, fn ($a, $b) => $a + self::getLineItemSubTotal($b), 0), 2);
    $totalAfterTax = round(array_reduce($items,
      fn ($a, $b) => $a + ($b['tax_amount'] ?? (($b['tax_rate'] * self::getLineItemSubTotal($b)) / 100)),
      0
    ) + $totalBeforeTax, 2);

    return [
      'taxRates' => self::computeLineItemsTaxRates($items),
      'totalAfterTax' => $totalAfterTax,
      'totalBeforeTax' => $totalBeforeTax,
    ];
  }

  /**
   * Computes the sub total of a single line item.
   *
   * @param array $item
   *   Single credit note line item.
   *
   * @return int
   *   The line item subtotal.
   */
  private static function getLineItemSubTotal(array $item) {
    return $item['unit_price'] * $item['quantity'] ?? 0;
  }

  /**
   * Computes the tax rates of each line item.
   *
   * @param array $items
   *   Single credit note line item.
   *
   * @return array
   *   Returned sorted array of line items tax rates.
   */
  private static function computeLineItemsTaxRates(array $items) {
    $items = array_filter($items, fn ($a) => !empty($a['tax_rate']) && $a['tax_rate'] > 0);
    usort($items, fn ($a, $b) => $a['tax_rate'] <=> $b['tax_rate']);

    return array_map(
      fn ($a) =>
      [
        'tax_name' => $a['tax_name'] ?? '',
        'rate' => round($a['tax_rate'], 2),
        'value' => round(($a['tax_rate'] * self::getLineItemSubTotal($a)) / 100, 2),
      ],
      $items
    );
  }

  /**
   * Creates a credit note with accounting entries.
   *
   * @param array $data
   *   The credit note data.
   * @param int|null $financialTypeId
   *   The financial type for the first credit note line.
   *
   * @return array
   *   An array containing the created credit note and its financial transaction.
   */
  public static function createWithAccountingEntries(array $data, ?int $financialTypeId): array {
    $creditNote = self::create($data)->toArray();
    $data = array_merge($creditNote, ['total_credit' => $creditNote['total_credit'] * -1]);

    $data['to_account_id'] = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');
    $financialTrxn = self::createAccountingEntries($data, 'Pending');

    return [
      'creditNote' => $creditNote,
      'financialTrxn' => $financialTrxn,
    ];
  }

  /**
   * Creates accounting entries for a credit note.
   *
   * @param array $data
   *   The credit note and payment data.
   * @param string $status
   *   The status to set for the transaction.
   *
   * @return array
   *   The created financial transaction.
   */
  private static function createAccountingEntries(array $data, string $status): array {
    $contributionStatus = \CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $statusId = array_search($status, $contributionStatus);

    $trxnParams = [
      'from_financial_account_id' => $data['from_account_id'] ?? NULL,
      'to_financial_account_id' => $data['to_account_id'] ?? NULL,
      'trxn_date' => $data['date'],
      'total_amount' => $data['total_credit'],
      'currency' => $data['currency'],
      'is_payment' => $data['is_payment'] ?? 0,
      'status_id' => $statusId,
      'payment_processor_id' => NULL,
      'payment_instrument_id' => $data['payment_instrument_id'] ?? self::getAccountReceivableId(),
      'card_type_id' => $data['card_type_id'] ?? NULL,
      'check_number' => $data['check_number'] ?? NULL,
      'pan_truncation' => $data['pan_truncation'] ?? NULL,
      'trxn_id' => $data['trxn_id'] ?? NULL,
      'fee_amount' => $data['fee_amount'] ?? NULL,
      'entity_table' => \CRM_Financeextras_DAO_CreditNote::$_tableName,
      'entity_id' => $data['id'],
    ];
    return \CRM_Core_BAO_FinancialTrxn::create($trxnParams)->toArray();
  }

  /**
   * Returns the account receivable payment method ID
   *
   * @return int
   *   Account receivable ID
   */
  private static function getAccountReceivableId() {
    return OptionValueUtils::getValueForOptionValue('payment_instrument', AccountsReceivablePaymentMethod::NAME);
  }

  /**
   * Voids a credit note.
   *
   * @param int $creditNoteId
   *   The ID of the credit note to void.
   * @param int|null $financialTypeId
   *   The financial type for the credit note.
   * @return array
   *   The voided financial transaction.
   */
  public static function voidCreditNote(int $creditNoteId, ?int $financialTypeId): array {
    $creditNoteBAO = new CRM_Financeextras_DAO_CreditNote();
    $creditNoteBAO->id = $creditNoteId;
    $creditNoteBAO->find(TRUE);

    $creditNoteBAO->status_id = OptionValueUtils::getValueForOptionValue(CreditNoteStatus::NAME, 'void');
    $creditNoteBAO->update();

    $data = $creditNoteBAO->toArray();
    $data['to_account_id'] = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');
    return self::createAccountingEntries($data, 'Cancelled');
  }

  /**
   * Deletes credit note accounting entries.
   *
   * @param int $creditNoteId
   *  The credit note unique identifier.
   */
  public static function deleteAccountingEntries($creditNoteId): void {
    $entityTrxn = new \CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->entity_table = \CRM_Financeextras_DAO_CreditNote::$_tableName;
    $entityTrxn->entity_id = $creditNoteId;
    $entityTrxn->find(TRUE);

    $trxn = new \CRM_Financial_DAO_FinancialTrxn();
    $trxn->deleteRecord(['id' => $entityTrxn->financial_trxn_id]);

    $entityTrxn->delete();

    CreditNoteAllocationBAO::deleteAccountingEntries($creditNoteId);
  }

  /**
   * Updates a cedit note status appropraitely post allocation
   *
   * @param int $allocationId
   *  The credit note for which allocation was made.
   */
  public static function updateCreditNoteStatusPostAllocation($allocationId) {
    $allocation = \Civi\Api4\CreditNoteAllocation::get(FALSE)
      ->addWhere('id', '=', $allocationId)
      ->addChain('credit_note', \Civi\Api4\CreditNote::get(FALSE)
        ->addWhere('id', '=', '$credit_note_id')
        ->addWhere('status_id:name', 'IN', ['open', 'fully_allocated'])
        )
      ->execute()
      ->first();
    $creditNote = $allocation['credit_note'][0] ?? NULL;

    if (empty($creditNote)) {
      return;
    }

    $status = match(TRUE) {
      $creditNote['remaining_credit'] <= 0 => 'fully_allocated',
      default => 'open'
    };

    \Civi\Api4\CreditNote::update(FALSE)
      ->addValue('status_id:name', $status)
      ->addWhere('id', '=', $creditNote['id'])
      ->execute();
  }

  /**
   * Refund credit note and create assocaited accounting entries.
   *
   * @param int $creditNoteId
   *   The ID of the credit note to void.
   * @param array $allocationParam
   *
   * @param array $paymentParam
   *  Payment Parameters
   *
   * @return array
   *   The credit note refund allocation data
   */
  public static function refund($creditNoteId, $allocationParam, $paymentParam) {
    $creditNote = \Civi\Api4\CreditNote::get(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->addChain('line', \Civi\Api4\CreditNoteLine::get(FALSE)
        ->addWhere('credit_note_id', '=', '$id')
      )
      ->execute()
      ->first();

    if ($creditNote['remaining_credit'] < $allocationParam['amount']) {
      throw new CRM_Core_Exception("Amount to be refunded cannot exceed the remaining credit");
    }
    $financialTrxn = self::createRefundAccountingEntries($creditNote, array_merge($allocationParam, $paymentParam));
    $allocation = self::createRefundAllocation($creditNote, $allocationParam);
    CreditNoteAllocationBAO::createAllocationEntityTransactions($allocation['id'], $financialTrxn['id'], -$allocationParam['amount']);

    return array_merge($allocation, ['financial_trxn_id' => $financialTrxn['id']]);
  }

  private static function createRefundAllocation(array $creditNote, array $data) {
    return \Civi\Api4\CreditNoteAllocation::create(FALSE)
      ->addValue('credit_note_id', $creditNote['id'])
      ->addValue('type_id:name', 'manual_refund_payment')
      ->addValue('currency', $creditNote['currency'])
      ->addValue('reference', $data['reference'])
      ->addValue('amount', $data['amount'])
      ->addValue('date', $data['date'])
      ->execute()
      ->first();
  }

  private static function createRefundAccountingEntries(array $creditNote, $data) {
    $data = array_merge($creditNote, ['total_credit' => $data['amount'] * -1], $data);
    $financialTypeId = $creditNote['line'][0]['financial_type_id'];
    $data['from_account_id'] = FinancialAccountUtils::getFinancialTypeAccount($financialTypeId, 'Accounts Receivable Account is');
    $data['to_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($data['payment_instrument_id']);
    $data['is_payment'] = 1;
    $financialTrxn = self::createAccountingEntries($data, 'Completed');

    $percent = 100 * $data['amount'] / $creditNote['total_credit'];

    foreach ($creditNote['line'] as $creditNoteLine) {
      $amount = ($creditNoteLine['line_total'] + $creditNoteLine['tax_amount']) * $percent * 0.01 * -1;
      CRM_Financeextras_BAO_CreditNoteLine::refundAccountingEntries($creditNoteLine['id'], $financialTrxn['id'], $amount);
    }

    return $financialTrxn;
  }

  /**
   * Gets the company record associated
   * with the credit note owner organisation.
   *
   * @param int $creditNoteId
   * @return array
   */
  public static function getOwnerOrganisationCompany($creditNoteId) {
    $OwnerOrgQuery = "SELECT contact.organization_name as name, contact.image_URL as logo_url, company.* FROM financeextras_credit_note cn
                      INNER JOIN financeextras_company company ON cn.owner_organization = company.contact_id
                      INNER JOIN civicrm_contact contact ON company.contact_id = contact.id
                      WHERE cn.id = {$creditNoteId}
                      LIMIT 1";
    $cnOwnerCompany = CRM_Core_DAO::executeQuery($OwnerOrgQuery);
    $cnOwnerCompany->fetch();
    return $cnOwnerCompany->toArray();
  }

}
