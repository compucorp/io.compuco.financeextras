<?php

/**
 * Imports a single CSV row into the Credit Note system.
 */
class CRM_Financeextras_CreditNoteImporter_CSVRowImporter {

  /**
   * The single CSV row data.
   *
   * @var array
   */
  private array $rowData;

  /**
   * @var int
   */
  private int $contactId;

  /**
   * @var int
   */
  private int $ownerOrganisationId;

  /**
   * @var int
   */
  private int $financialTypeId;

  /**
   * @var array
   */
  private array $cachedValues = [];

  public function __construct(array $rowData) {
    $this->rowData = $rowData;
  }

  /**
   * Imports the row.
   *
   * @return int
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   * @throws Throwable
   */
  public function import(): int {
    $this->validateRequiredFields();

    $this->contactId = $this->resolveContactId();
    $this->ownerOrganisationId = $this->resolveOwnerOrganisationId();
    $this->financialTypeId = $this->resolveFinancialTypeId($this->rowData['line_financial_type']);
    $externalId = (string) $this->rowData['credit_note_external_id'];
    $existingCreditNoteId = $this->findCreditNoteIdByExternalId($externalId);
    $lineItem = $this->buildLineItemPayload();
    $transaction = CRM_Core_Transaction::create();
    try {
      if ($existingCreditNoteId === NULL) {
        $creditNoteId = $this->createCreditNoteWithFirstLine($lineItem, $externalId);
      }
      else {
        $creditNoteId = $existingCreditNoteId;
        $this->appendLineToExistingCreditNote($creditNoteId, $lineItem);
      }
    }
    catch (\Throwable $e) {
      $transaction->rollback();
      throw $e;
    }

    $transaction->commit();

    return $creditNoteId;
  }

  /**
   * Validates that the minimum set of CSV columns is present.
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function validateRequiredFields(): void {
    $required = [
      'credit_note_external_id',
      'currency',
      'line_unit_price',
      'line_financial_type',
    ];
    foreach ($required as $field) {
      if (!isset($this->rowData[$field]) || empty($this->rowData[$field])) {
        throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
          sprintf(ts('Missing required field "%s".'), $field)
        );
      }
    }

    if (empty($this->rowData['contact_id']) && empty($this->rowData['contact_external_id'])) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        'Either "contact_id" or "contact_external_id" is required.'
      );
    }

    if (empty($this->rowData['owner_organization_id']) && empty($this->rowData['owner_organization_external_id'])) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        'Either "owner_organization_id" or "owner_organization_external_id" is required.'
      );
    }
  }

  /**
   * Resolves the customer contact ID.
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function resolveContactId(): int {
    if (!empty($this->rowData['contact_id'])) {
      $contact = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('id')
        ->addWhere('id', '=', $this->rowData['contact_id'])
        ->addWhere('is_deleted', '=', FALSE)
        ->execute()
        ->first();
      if (empty($contact)) {
        throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
          sprintf('Cannot find contact with id "%s".', $this->rowData['contact_id'])
        );
      }
      return (int) $contact['id'];
    }

    $contact = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('external_identifier', '=', $this->rowData['contact_external_id'])
      ->addWhere('is_deleted', '=', FALSE)
      ->execute()
      ->first();
    if (empty($contact)) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        sprintf('Cannot find contact with external identifier "%s".', $this->rowData['contact_external_id'])
      );
    }
    return (int) $contact['id'];
  }

  /**
   * Resolves the owning organisation contact ID.
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function resolveOwnerOrganisationId(): int {
    if (!empty($this->rowData['owner_organization_id'])) {
      $org = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('id')
        ->addWhere('id', '=', $this->rowData['owner_organization_id'])
        ->addWhere('contact_type', '=', 'Organization')
        ->addWhere('is_deleted', '=', FALSE)
        ->execute()
        ->first();
      if (empty($org)) {
        throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
          sprintf('Cannot find organisation with id "%s".', $this->rowData['owner_organization_id'])
        );
      }
      return (int) $org['id'];
    }

    $org = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id')
      ->addWhere('external_identifier', '=', $this->rowData['owner_organization_external_id'])
      ->addWhere('contact_type', '=', 'Organization')
      ->addWhere('is_deleted', '=', FALSE)
      ->execute()
      ->first();
    if (empty($org)) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        sprintf('Cannot find organisation with external identifier "%s".', $this->rowData['owner_organization_external_id'])
      );
    }
    return (int) $org['id'];
  }

  /**
   * Resolves a financial type by name.
   *
   * @param string $financialTypeName
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function resolveFinancialTypeId(string $financialTypeName): int {
    if (!isset($this->cachedValues['financial_types'])) {
      $this->cachedValues['financial_types'] = [];
      $financialTypes = \Civi\Api4\FinancialType::get(FALSE)
        ->addSelect('id', 'name')
        ->addWhere('is_active', '=', TRUE)
        ->execute();
      foreach ($financialTypes as $type) {
        $this->cachedValues['financial_types'][$type['name']] = (int) $type['id'];
      }
    }

    if (empty($this->cachedValues['financial_types'][$financialTypeName])) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        sprintf('Invalid line financial type "%s".', $financialTypeName)
      );
    }

    return $this->cachedValues['financial_types'][$financialTypeName];
  }

  /**
   * Returns the credit note date, defaulting to today's date.
   *
   * @param mixed $value
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function resolveCreditNoteDate($value): string {
    if (empty($value)) {
      return date('Y-m-d');
    }

    $timestamp = strtotime((string) $value);
    if ($timestamp === FALSE) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        sprintf('Could not parse date "%s". Use a format like YYYY-MM-DD.', $value)
      );
    }

    if ($timestamp <= 0) {
      return date('Y-m-d');
    }

    return date('Y-m-d', $timestamp);
  }

  /**
   * Builds a line-item payload.
   *
   * @return array
   */
  private function buildLineItemPayload(): array {
    $quantity = !empty($this->rowData['line_quantity']) ? (float) $this->rowData['line_quantity'] : 1;
    $unitPrice = (float) $this->rowData['line_unit_price'];

    return [
      'description' => $this->rowData['line_description'] ?? '',
      'quantity' => $quantity,
      'unit_price' => $unitPrice,
      'financial_type_id' => $this->financialTypeId,
      'tax_rate' => $this->getTaxRateForFinancialType($this->financialTypeId),
    ];
  }

  /**
   * Returns the tax rate (percentage) configured for the given financial type.
   */
  private function getTaxRateForFinancialType(int $financialTypeId): float {
    if (!isset($this->cachedValues['tax_rates'])) {
      $this->cachedValues['tax_rates'] = \CRM_Core_PseudoConstant::getTaxRates();
    }

    return (float) ($this->cachedValues['tax_rates'][$financialTypeId] ?? 0);
  }

  /**
   * Looks up an existing credit note by external id.
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function findCreditNoteIdByExternalId(string $externalId): ?int {
    if (!\CRM_Core_DAO::checkTableExists('civicrm_value_credit_note_ext_id')) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        'One required custom group is missing. Run the financeextras upgrade before importing credit notes.'
      );
    }

    $id = \CRM_Core_DAO::singleValueQuery(
      'SELECT entity_id FROM civicrm_value_credit_note_ext_id WHERE external_id = %1',
      [1 => [$externalId, 'String']]
    );
    return $id !== NULL ? (int) $id : NULL;
  }

  /**
   * Persists the external-id <-> credit-note mapping.
   *
   * See the comment on findCreditNoteIdByExternalId() for why we use
   * raw SQL here instead of APIv4 update with the custom field.
   */
  private function recordExternalIdForCreditNote(int $creditNoteId, string $externalId): void {
    \CRM_Core_DAO::executeQuery(
      'INSERT INTO civicrm_value_credit_note_ext_id (entity_id, external_id) VALUES (%1, %2)',
      [
        1 => [$creditNoteId, 'Integer'],
        2 => [$externalId, 'String'],
      ]
    );
  }

  /**
   * Creates a brand-new credit note with its first line item.
   *
   * @param array $lineItem
   *   Line-item payload built by buildLineItemPayload().
   * @param string $externalId
   *   The external identifier supplied for the credit note.
   */
  private function createCreditNoteWithFirstLine(array $lineItem, string $externalId): int {
    $totals = \CRM_Financeextras_BAO_CreditNote::computeTotalAmount([$lineItem]);

    $optionValue = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_status')
      ->addWhere('name', '=', 'open')
      ->execute()
      ->first();

    $creditNoteData = [
      'contact_id' => $this->contactId,
      'owner_organization' => $this->ownerOrganisationId,
      'cn_number' => !empty($this->rowData['cn_number']) ? $this->rowData['cn_number'] : NULL,
      'date' => $this->resolveCreditNoteDate($this->rowData['date'] ?? NULL),
      'status_id' => ($optionValue && $optionValue['value']) ? $optionValue['value'] : NULL,
      'reference' => $this->rowData['reference'] ?? NULL,
      'currency' => $this->rowData['currency'],
      'description' => $this->rowData['description'] ?? '',
      'comment' => $this->rowData['comment'] ?? '',
      'subtotal' => $totals['totalBeforeTax'],
      'sales_tax' => array_sum(array_column($totals['taxRates'], 'value')),
      'total_credit' => $totals['totalAfterTax'],
    ];

    $created = \CRM_Financeextras_BAO_CreditNote::createWithAccountingEntries($creditNoteData, $this->financialTypeId);
    $creditNote = $created['creditNote'];
    $financialTrxn = $created['financialTrxn'];

    $this->recordExternalIdForCreditNote((int) $creditNote['id'], $externalId);

    \CRM_Financeextras_BAO_CreditNoteLine::createWithAcountingEntries([$lineItem], $creditNote, $financialTrxn);

    return (int) $creditNote['id'];
  }

  /**
   * Adds an additional line and its accounting entries to a credit note.
   *
   * @param int $creditNoteId
   * @param array $lineItem
   */
  private function appendLineToExistingCreditNote(int $creditNoteId, array $lineItem): void {
    $creditNote = \Civi\Api4\CreditNote::get(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    if (empty($creditNote)) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        sprintf('Credit note with id "%d" referenced by mapping table no longer exists.', $creditNoteId)
      );
    }

    $this->assertRowIsConsistentWithCreditNote($creditNote);

    $financialTrxn = $this->getCreditNoteFinancialTrxn($creditNoteId);
    if (empty($financialTrxn)) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(
        sprintf('Credit note "%d" is missing its financial transaction; cannot append line.', $creditNoteId)
      );
    }

    \CRM_Financeextras_BAO_CreditNoteLine::createWithAcountingEntries([$lineItem], $creditNote, $financialTrxn);

    [$newSubtotal, $newSalesTax, $newTotalCredit] = $this->aggregateLineTotals($creditNoteId);

    $this->setCreditNoteTotals($creditNoteId, $newSubtotal, $newSalesTax, $newTotalCredit);
    $this->setFinancialTrxnTotals($financialTrxn, $creditNoteId, $newTotalCredit);
  }

  /**
   * Guards against appending a row to a credit note with wrong customer or owning organisation.
   *
   * @param array $creditNote
   *   APIv4 CreditNote::get result for the existing credit note.
   *
   * @throws CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException
   */
  private function assertRowIsConsistentWithCreditNote(array $creditNote): void {
    if ((int) $creditNote['contact_id'] !== $this->contactId) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(sprintf(
        'Row references an existing credit note (id %d, external id "%s") but its contact id (%d) does not match the credit note\'s contact (%d). All rows that share a credit_note_external_id must use the same contact.',
        $creditNote['id'],
        $this->rowData['credit_note_external_id'],
        $this->contactId,
        $creditNote['contact_id']
      ));
    }

    if ((int) $creditNote['owner_organization'] !== $this->ownerOrganisationId) {
      throw new CRM_Financeextras_CreditNoteImporter_Exception_InvalidRowException(sprintf(
        'Row references an existing credit note (id %d, external id "%s") but its owner organisation (%d) does not match the credit note\'s owner (%d). All rows that share a credit_note_external_id must use the same owner organisation.',
        $creditNote['id'],
        $this->rowData['credit_note_external_id'],
        $this->ownerOrganisationId,
        $creditNote['owner_organization']
      ));
    }
  }

  /**
   * Aggregates the line totals already persisted for the credit note.
   *
   * Returns [subtotal, sales_tax, total_credit].
   */
  private function aggregateLineTotals(int $creditNoteId): array {
    $row = \Civi\Api4\CreditNoteLine::get(FALSE)
      ->addSelect('SUM(line_total) AS subtotal', 'SUM(tax_amount) AS sales_tax')
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute()
      ->first();

    if (!is_array($row)) {
      return [0.0, 0.0, 0.0];
    }

    $subtotal = (float) ($row['subtotal'] ?? 0);
    $salesTax = (float) ($row['sales_tax'] ?? 0);

    return [
      $subtotal,
      $salesTax,
      $subtotal + $salesTax,
    ];
  }

  /**
   * Returns the single financial transaction associated with a credit note.
   */
  private function getCreditNoteFinancialTrxn(int $creditNoteId): array {
    $entityTrxn = \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addSelect('financial_trxn_id')
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->addWhere('entity_id', '=', $creditNoteId)
      ->addOrderBy('id')
      ->setLimit(1)
      ->execute()
      ->first();

    if (empty($entityTrxn) || empty($entityTrxn['financial_trxn_id'])) {
      return [];
    }

    return \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $entityTrxn['financial_trxn_id'])
      ->execute()
      ->first() ?? [];
  }

  /**
   * Sets stored totals on the credit note record to the supplied values.
   */
  private function setCreditNoteTotals(int $creditNoteId, float $subtotal, float $salesTax, float $totalCredit): void {
    \Civi\Api4\CreditNote::update(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->addValue('subtotal', $subtotal)
      ->addValue('sales_tax', $salesTax)
      ->addValue('total_credit', $totalCredit)
      ->execute();
  }

  /**
   * Sets the totals on the financial transaction backing the credit note.
   *
   * @param array $financialTrxn
   * @param int $creditNoteId
   * @param float $totalCredit
   */
  private function setFinancialTrxnTotals(array $financialTrxn, int $creditNoteId, float $totalCredit): void {
    $negatedTotal = -1 * $totalCredit;
    $financialTrxnId = (int) $financialTrxn['id'];

    $trxnUpdate = \Civi\Api4\FinancialTrxn::update(FALSE)
      ->addWhere('id', '=', $financialTrxnId)
      ->addValue('total_amount', $negatedTotal);
    if (isset($financialTrxn['net_amount']) && $financialTrxn['net_amount'] !== NULL) {
      $trxnUpdate->addValue('net_amount', $negatedTotal);
    }
    $trxnUpdate->execute();

    \Civi\Api4\EntityFinancialTrxn::update(FALSE)
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->addWhere('entity_id', '=', $creditNoteId)
      ->addWhere('financial_trxn_id', '=', $financialTrxnId)
      ->addValue('amount', $negatedTotal)
      ->execute();
  }

}
