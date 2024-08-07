<?php

namespace Civi\Financeextras\Hook\Post;

use CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation as ContributionOwnerOrganisation;

class ContributionCreation {

  private $contribution;

  private $ownerOrganizationId;

  private static $paymentPlanOwnerOrganization = [];

  private static $incomeAccountRelationId;

  public function __construct($contributionId) {
    if (empty(self::$incomeAccountRelationId)) {
      self::$incomeAccountRelationId = key(\CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    }

    $this->contribution = $this->getContributionData((int) $contributionId);
    $this->setOwnerOrganizationId();
  }

  /**
   * Sets the owner organization id based on
   * 3 steps approach, which is needed to handle
   * contributions being created from different
   * screens and places.
   *
   * 1- If the contribution belongs to a payment plan
   * created using the membership form or through autorenewal,
   * then get it from the payment plan line items.
   *
   * 2- Otherwise we look into the contribution
   * line items.
   *
   * 3- In cases where line items are created after the contribution,
   * such as event registration or Webform submission, we get it directly
   * from the contribution 'financial_type_id', though the event or
   * the Webform has to be configured in certain way that can be found
   * in this extension documentation.
   *
   * @return void
   */
  private function setOwnerOrganizationId() {
    $ownerOrganizationId = NULL;

    $contribution = $this->contribution;
    if (!empty($contribution['contribution_recur_id'])) {
      $ownerOrganizationId = $this->getOwnerOrganizationIdFromPaymentPlanLineItems($contribution['contribution_recur_id']);
    }

    if (empty($ownerOrganizationId)) {
      $ownerOrganizationId = $this->getOwnerOrganizationIdFromContributionLineItems();
    }

    if (empty($ownerOrganizationId) && !empty($contribution['financial_type_id'])) {
      $ownerOrganizationId = $this->getOwnerOrganizationIdFromContributionFinancialType($contribution['financial_type_id']);
    }

    $this->ownerOrganizationId = $ownerOrganizationId;
  }

  private function getContributionData(int $contributionId) {
    $result = civicrm_api3('Contribution', 'get', [
      'sequential' => 1,
      'return' => ['contribution_recur_id', 'financial_type_id', 'is_pay_later'],
      'id' => $contributionId,
    ]);

    if (empty($result['values'][0])) {
      return NULL;
    }

    return $result['values'][0];
  }

  /**
   * Gets the owner organization from the owner
   * of the income account for the financial type of
   * the recurring contribution active and renewable line items.
   *
   * And while this extension does not depend on "Membershipextras" extension,
   * This method will only return result if "Membershipextras" is installed,
   * which means only "Membershipextras" payment plans are supported.
   *
   * @return mixed|string|null
   */
  private function getOwnerOrganizationIdFromPaymentPlanLineItems() {
    $recurContributionId = $this->contribution['contribution_recur_id'];
    if (!empty(self::$paymentPlanOwnerOrganization[$recurContributionId])) {
      return self::$paymentPlanOwnerOrganization[$recurContributionId];
    }
    self::$paymentPlanOwnerOrganization = [];

    $incomeAccountRelationId = self::$incomeAccountRelationId;
    $query = "SELECT fa.contact_id FROM civicrm_contribution_recur cr
                            INNER JOIN membershipextras_subscription_line msl ON cr.id = msl.contribution_recur_id
                            INNER JOIN civicrm_line_item li ON msl.line_item_id = li.id
                            INNER JOIN civicrm_entity_financial_account efa ON li.financial_type_id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
                            INNER JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
                            WHERE cr.id = {$recurContributionId} AND efa.account_relationship = {$incomeAccountRelationId}
                            AND msl.auto_renew = 1 AND msl.is_removed = 0
                            ORDER BY msl.id DESC
                            LIMIT 1";
    $ownerOrgId = \CRM_Core_DAO::singleValueQuery($query);

    self::$paymentPlanOwnerOrganization[$recurContributionId] = $ownerOrgId;
    return $ownerOrgId;
  }

  /**
   * Gets the owner organization from the owner
   * of the income account for the financial type of
   * the contribution line items.
   *
   * @return string|null
   */
  private function getOwnerOrganizationIdFromContributionLineItems() {
    $incomeAccountRelationId = self::$incomeAccountRelationId;
    $query = "SELECT fa.contact_id FROM civicrm_line_item li
                      INNER JOIN civicrm_entity_financial_account efa ON li.financial_type_id = efa.entity_id AND efa.entity_table = 'civicrm_financial_type'
                      INNER JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
                      WHERE efa.account_relationship = {$incomeAccountRelationId} AND li.contribution_id = {$this->contribution['id']}
                      LIMIT 1";
    return \CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Gets the owner organization from the owner
   * of the income account for the financial type of
   * the contribution itself.
   *
   * @param $financialTypeId
   * @return string|null
   */
  private function getOwnerOrganizationIdFromContributionFinancialType($financialTypeId) {
    $incomeAccountRelationId = self::$incomeAccountRelationId;
    $query = "SELECT fa.contact_id FROM civicrm_entity_financial_account efa
                      INNER JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
                      WHERE efa.entity_id = {$financialTypeId} AND efa.entity_table = 'civicrm_financial_type' AND efa.account_relationship = {$incomeAccountRelationId}
                      LIMIT 1";
    return \CRM_Core_DAO::singleValueQuery($query);
  }

  public function run() {
    if (!empty($this->ownerOrganizationId)) {
      $this->updateOwnerOrganization();
      $this->updateContribution();
    }
    else {
      // this will terminate the Contribution transaction in CiviCRM core, which will trigger a rollback and prevent the contribution
      // from getting created.
      throw new \CRM_Core_Exception("Unable to set the owner organisation and the invoice number for the contribution with id: {$this->contribution['id']}.");
    }
  }

  /**
   * Stores the contribution owner organization.
   *
   * @return void
   */
  private function updateOwnerOrganization() {
    ContributionOwnerOrganisation::setOwnerOrganisation($this->contribution['id'], $this->ownerOrganizationId);
  }

  /**
   * Calculates and stores the contribution invoice
   * number.
   * The invoice number is calculated as the following:
   *
   * 1- Using the contribution owner organization, we get
   * its related Company record, which contains the invoice
   * prefix, next invoice number and receivable payment method. The value is read using
   * 'SELECT FOR UPDATE' to acquire a row level lock, to prevent
   * any other contribution from using the same invoice number.
   * The lock works because CiviCRM starts a transaction while
   * creating the contribution, then at some transaction it triggers this
   * hook, then later it commits the transaction. So the queries
   * here runs as part of the contribution transaction.
   *
   * 2- Then the prefix is appended to the invoice number, this
   * will be the contribution invoice number.
   *
   * 3- Then the next invoice number is incremented by one
   * while leading zeros are preserved.
   *
   * 4- The contribution invoice_number is set
   * to the invoice number in from step 2.
   *
   * 5- If the contribution is pay later than we update the payment_instrument as well
   * for the contribution and financial trxn table.
   *
   * When the controls get back to CiviCRM core,
   * CiviCRM will commit the transaction, and thus
   * the lock on the Company row will be released.
   *
   * @return void
   */
  private function updateContribution() {
    $companyRecord = \CRM_Core_DAO::executeQuery("SELECT invoice_prefix, next_invoice_number, receivable_payment_method FROM financeextras_company WHERE contact_id = {$this->ownerOrganizationId} FOR UPDATE");
    $companyRecord->fetch();

    $this->setInvoiceNumber($companyRecord);
    $this->setPaymentinstrumentId($companyRecord);
  }

  /**
   * Sets the contribution invoice number.
   *
   * @param \CRM_Core_DAO|object $companyRecord
   *  The company record.
   */
  private function setInvoiceNumber($companyRecord) {
    $invoiceNumber = $companyRecord->next_invoice_number;
    if (!empty($companyRecord->invoice_prefix)) {
      $invoiceNumber = $companyRecord->invoice_prefix . $companyRecord->next_invoice_number;
    }

    $invoiceUpdateSQLFormula = $this->getInvoiceNumberUpdateSQLFormula($companyRecord->next_invoice_number);
    \CRM_Core_DAO::executeQuery("UPDATE financeextras_company SET next_invoice_number = {$invoiceUpdateSQLFormula}  WHERE contact_id = {$this->ownerOrganizationId}");

    \CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution SET invoice_number = '{$invoiceNumber}' WHERE id = {$this->contribution['id']}");
  }

  /**
   * Sets the payment instrument id for the contribution.
   *
   * @param \CRM_Core_DAO|object $companyRecord
   *  The company record.
   */
  public function setPaymentInstrumentId($companyRecord) {
    if (!$this->isContributionNotRecordingPayment()) {
      return;
    }

    $entityFinancialTransaction = \CRM_Core_DAO::executeQuery("SELECT financial_trxn_id FROM civicrm_entity_financial_trxn WHERE entity_id = {$this->contribution['id']} AND entity_table = 'civicrm_contribution'");
    $entityFinancialTransaction->fetch();

    $financialTrnxId = $entityFinancialTransaction->financial_trxn_id;
    if ($this->contributionPaymentProcessorIsValid($financialTrnxId)) {
      \CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution SET payment_instrument_id = '{$companyRecord->receivable_payment_method}' WHERE id = {$this->contribution['id']}");
      \CRM_Core_DAO::executeQuery("UPDATE civicrm_financial_trxn SET payment_instrument_id = '{$companyRecord->receivable_payment_method}' WHERE id = {$financialTrnxId}");
    }
  }

  /**
   * Gets the SQL formula to update the invoice
   * number, where if the invoice starts with
   * a zero, then it means it has a leading zero(s)
   * and thus they should be respected, or otherwise
   * the invoice number would be incremented
   * normally.
   *
   * @param $invoiceNumberNumericPart
   * @return string
   */
  private function getInvoiceNumberUpdateSQLFormula($invoiceNumberNumericPart) {
    $firstZeroLocation = strpos($invoiceNumberNumericPart, '0');
    $isThereLeadingZero = $firstZeroLocation === 0;
    if ($isThereLeadingZero) {
      $invoiceNumberCharCount = strlen($invoiceNumberNumericPart);
      $invoiceUpdateFormula = "LPAD((next_invoice_number + 1), {$invoiceNumberCharCount}, '0')";
    }
    else {
      $invoiceUpdateFormula = "(next_invoice_number + 1)";
    }

    return $invoiceUpdateFormula;
  }

  private function isContributionNotRecordingPayment(): bool {
    return (
      $this->contribution['is_pay_later'] &&
      (empty($_POST['fe_record_payment_check']) || !empty($_POST['payment_plan_schedule']))
    );
  }

  /**
   * Checks if the contribution payment processor type is not one of the specified types.
   *
   * @param int $financialTrnxId
   *  The ID of the financial transaction.
   * @return bool
   *   TRUE if the payment processor type is not one of the specified types, FALSE otherwise.
   */
  private function contributionPaymentProcessorIsValid($financialTrnxId): bool {
    $paymentProcessorTypes = ['OfflineDirectDebit', 'Manual_Recurring_Payment'];
    $financialTrxn = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addSelect('payment_processor_id:name', 'payment_processor.payment_processor_type_id:name', 'payment_instrument_id:name')
      ->addJoin('PaymentProcessor AS payment_processor', 'LEFT')
      ->addWhere('id', '=', $financialTrnxId)
      ->execute()->first() ?? NULL;

    if (empty($financialTrxn)) {
      return TRUE;
    }

    return !in_array($financialTrxn['payment_processor.payment_processor_type_id:name'], $paymentProcessorTypes)
      && $financialTrxn['payment_instrument_id:name'] != 'direct_debit';
  }

}
