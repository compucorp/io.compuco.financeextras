<?php

/**
 * CreditNoteAllocation APIv3.
 */

use Civi\Financeextras\Utils\OptionValueUtils;

/**
 * CreditNoteAllocation.create API.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_credit_note_allocation_create($params) {
  if (!empty($params['id'])) {
    throw new CiviCRM_API3_Exception(ts('Updating credit note allocations through APIv3 is not supported. Use the APIv4 reverse action to reverse an allocation.'));
  }

  _civicrm_api3_credit_note_allocation_validate_type($params);
  _civicrm_api3_credit_note_allocation_resolve_pseudo_constants($params);

  $creditNote = _civicrm_api3_credit_note_allocation_fetch_credit_note($params['credit_note_id']);
  $contribution = _civicrm_api3_credit_note_allocation_fetch_contribution($params['contribution_id']);

  _civicrm_api3_credit_note_allocation_validate_amount($params, $creditNote);
  _civicrm_api3_credit_note_allocation_validate_contact_match($creditNote, $contribution);
  _civicrm_api3_credit_note_allocation_validate_currency_match($params, $creditNote, $contribution);

  $data = [
    'credit_note_id' => $params['credit_note_id'],
    'contribution_id' => $params['contribution_id'],
    'type_id' => $params['type_id'],
    'currency' => $params['currency'],
    'reference' => $params['reference'] ?? NULL,
    'amount' => $params['amount'],
    'date' => _civicrm_api3_credit_note_allocation_resolve_date($params['date'] ?? NULL),
  ];

  $allocation = CRM_Financeextras_BAO_CreditNoteAllocation::createWithAccountingEntries($data);

  return civicrm_api3_create_success([$allocation['id'] => $allocation], $params, 'CreditNoteAllocation', 'create');
}

/**
 * CreditNoteAllocation.create API specification.
 *
 * @param array $params
 */
function _civicrm_api3_credit_note_allocation_create_spec(&$params) {
  $params['credit_note_id'] = [
    'title' => ts('Credit Note ID'),
    'description' => ts('ID of the Credit Note credit is being allocated from.'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'FKClassName' => 'CRM_Financeextras_DAO_CreditNote',
    'FKApiName' => 'CreditNote',
  ];

  $params['contribution_id'] = [
    'title' => ts('Contribution ID'),
    'description' => ts('ID of the Contribution to which credit is being allocated.'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'FKClassName' => 'CRM_Contribute_DAO_Contribution',
    'FKApiName' => 'Contribution',
  ];

  $params['type_id'] = [
    'title' => ts('Allocation Type Id'),
    'description' => ts('One of the values of the financeextras_credit_note_allocation_type option group. Provide this OR type_name.'),
    'type' => CRM_Utils_Type::T_INT,
    'pseudoconstant' => [
      'optionGroupName' => 'financeextras_credit_note_allocation_type',
    ],
  ];

  $params['type_name'] = [
    'title' => ts('Allocation Type Name'),
    'description' => ts('The option value name from financeextras_credit_note_allocation_type (e.g. "invoice", "manual_refund_payment"). Provide this OR type_id.'),
    'type' => CRM_Utils_Type::T_STRING,
  ];

  $params['currency'] = [
    'title' => ts('Currency'),
    'description' => ts('3-letter currency code (e.g. USD, GBP).'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'maxlength' => 3,
  ];

  $params['amount'] = [
    'title' => ts('Amount'),
    'description' => ts('The amount of credit being allocated.'),
    'type' => CRM_Utils_Type::T_MONEY,
    'api.required' => 1,
  ];

  $params['date'] = [
    'title' => ts('Allocation Date'),
    'description' => ts('Date the allocation was made. Defaults to today if omitted.'),
    'type' => CRM_Utils_Type::T_DATE,
  ];

  $params['reference'] = [
    'title' => ts('Reference'),
    'description' => ts('Optional reference for the allocation.'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * CreditNoteAllocation.get API.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_credit_note_allocation_get($params) {
  return _civicrm_api3_basic_get('CRM_Financeextras_BAO_CreditNoteAllocation', $params);
}

/**
 * CreditNoteAllocation.delete API.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_credit_note_allocation_delete($params) {
  return _civicrm_api3_basic_delete('CRM_Financeextras_BAO_CreditNoteAllocation', $params);
}

/**
 * Enforces the rule that one of `type_id` or `type_name` must be supplied.
 *
 * @param array $params
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_validate_type(array $params) {
  if (empty($params['type_id']) && empty($params['type_name'])) {
    throw new CiviCRM_API3_Exception('Either type_id or type_name is required.');
  }
}

/**
 * Returns the allocation date, defaulting to today when the row didn't supply one.
 *
 * @param mixed $value
 *
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_resolve_date($value): string {
  if (empty($value)) {
    return date('Y-m-d');
  }

  $timestamp = strtotime((string) $value);
  if ($timestamp === FALSE) {
    throw new CiviCRM_API3_Exception(
      sprintf('Could not parse date "%s". Use a format like YYYY-MM-DD.', $value)
    );
  }

  if ($timestamp <= 0) {
    return date('Y-m-d');
  }

  return date('Y-m-d', $timestamp);
}

/**
 * Resolves pseudo-constant values that may have been supplied by name.
 *
 * @param array $params
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_resolve_pseudo_constants(array &$params) {
  if (empty($params['type_id']) && !empty($params['type_name'])) {
    $params['type_id'] = OptionValueUtils::getValueForOptionValue(
      'financeextras_credit_note_allocation_type',
      $params['type_name']
    );
  }
}

/**
 * Loads the credit note record once with every field the downstream validators need.
 *
 * @param int|string $creditNoteId
 *
 * @return array
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_fetch_credit_note($creditNoteId): array {
  $creditNote = \Civi\Api4\CreditNote::get(FALSE)
    ->addSelect('id', 'cn_number', 'contact_id', 'currency', 'total_credit', 'remaining_credit')
    ->addWhere('id', '=', $creditNoteId)
    ->execute()
    ->first();

  if (empty($creditNote)) {
    throw new CiviCRM_API3_Exception(
      sprintf('Credit note with id "%s" does not exist.', $creditNoteId)
    );
  }

  return $creditNote;
}

/**
 * Loads the contribution record once with the fields the validators need.
 *
 * @param int|string $contributionId
 *
 * @return array
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_fetch_contribution($contributionId): array {
  $contribution = \Civi\Api4\Contribution::get(FALSE)
    ->addSelect('id', 'contact_id', 'currency')
    ->addWhere('id', '=', $contributionId)
    ->execute()
    ->first();

  if (empty($contribution)) {
    throw new CiviCRM_API3_Exception(
      sprintf('Contribution with id "%s" does not exist.', $contributionId)
    );
  }

  return $contribution;
}

/**
 * Validates that the credit note and the target contribution belong to the same contact.
 *
 * @param array $creditNote
 *   Loaded credit note (must include `id`, `cn_number`, `contact_id`).
 * @param array $contribution
 *   Loaded contribution (must include `id`, `contact_id`).
 *
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_validate_contact_match(array $creditNote, array $contribution) {
  $reference = !empty($creditNote['cn_number']) ? $creditNote['cn_number'] : ('id ' . $creditNote['id']);

  if (empty($creditNote['contact_id'])) {
    throw new CiviCRM_API3_Exception(sprintf(
      'Cannot allocate credit note %s because it has no contact set; allocation requires the credit note and the contribution to belong to the same contact.',
      $reference
    ));
  }

  if ((int) $contribution['contact_id'] !== (int) $creditNote['contact_id']) {
    throw new CiviCRM_API3_Exception(sprintf(
      'Cannot allocate credit note %s (contact %d) to contribution %d (contact %d). The credit note and the contribution must belong to the same contact.',
      $reference,
      $creditNote['contact_id'],
      $contribution['id'],
      $contribution['contact_id']
    ));
  }
}

/**
 * Validates that the supplied currency is valid.
 *
 * @param array $params
 * @param array $creditNote
 *   Loaded credit note (must include `id`, `cn_number`, `currency`).
 * @param array $contribution
 *   Loaded contribution (must include `id`, `currency`).
 *
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_validate_currency_match(array $params, array $creditNote, array $contribution) {
  $reference = !empty($creditNote['cn_number']) ? $creditNote['cn_number'] : ('id ' . $creditNote['id']);
  $rowCurrency = (string) ($params['currency'] ?? '');

  if (!empty($creditNote['currency']) && strcasecmp($rowCurrency, (string) $creditNote['currency']) !== 0) {
    throw new CiviCRM_API3_Exception(sprintf(
      'Currency mismatch for credit note %s: row supplied "%s" but the credit note is denominated in "%s". The allocation, credit note and contribution must all use the same currency.',
      $reference,
      $rowCurrency,
      $creditNote['currency']
    ));
  }

  if (!empty($contribution['currency']) && strcasecmp($rowCurrency, (string) $contribution['currency']) !== 0) {
    throw new CiviCRM_API3_Exception(sprintf(
      'Currency mismatch for contribution %d: row supplied "%s" but the contribution is denominated in "%s". The allocation, credit note and contribution must all use the same currency.',
      $contribution['id'],
      $rowCurrency,
      $contribution['currency']
    ));
  }
}

/**
 * Validates that the requested allocation amount is valid.
 *
 * @param array $params
 * @param array $creditNote
 *
 * @throws CiviCRM_API3_Exception
 */
function _civicrm_api3_credit_note_allocation_validate_amount(array $params, array $creditNote) {
  $amount = (float) $params['amount'];

  if ($amount <= 0) {
    throw new CiviCRM_API3_Exception('Allocation amount must be greater than zero.');
  }

  $totalCredit = (float) ($creditNote['total_credit'] ?? 0);
  $remainingCredit = (float) ($creditNote['remaining_credit'] ?? 0);
  $alreadyAllocated = $totalCredit - $remainingCredit;

  if ($amount > $remainingCredit) {
    $reference = !empty($creditNote['cn_number']) ? $creditNote['cn_number'] : ('id ' . $creditNote['id']);
    throw new CiviCRM_API3_Exception(sprintf(
      'Allocation amount (%s) exceeds the remaining credit (%s) for credit note %s. Total credit is %s and %s has already been allocated.',
      number_format($amount, 2, '.', ''),
      number_format($remainingCredit, 2, '.', ''),
      $reference,
      number_format($totalCredit, 2, '.', ''),
      number_format($alreadyAllocated, 2, '.', '')
    ));
  }
}
