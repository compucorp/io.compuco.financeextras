<?php

/**
 * CreditNoteImporter APIv3.
 */

/**
 * CreditNoteImporter.create API.
 *
 * Imports a single CSV row.
 *
 * @param array $params
 * @return array API result descriptor
 * @throws CiviCRM_API3_Exception
 */
function civicrm_api3_credit_note_importer_create($params) {
  try {
    $importer = new CRM_Financeextras_CreditNoteImporter_CSVRowImporter($params);
    $creditNoteId = $importer->import();
  }
  catch (Exception $exception) {
    return civicrm_api3_create_error($exception->getMessage());
  }

  return civicrm_api3_create_success(
    [
      $creditNoteId => [
        'id' => $creditNoteId,
        'credit_note_id' => $creditNoteId,
      ],
    ],
    $params,
    'CreditNoteImporter',
    'create'
  );
}

/**
 * CreditNoteImporter.create API specification.
 *
 * @param array $params
 */
function _civicrm_api3_credit_note_importer_create_spec(&$params) {
  $params['credit_note_external_id'] = [
    'title' => ts('Credit Note External Id'),
    'description' => ts('External identifier for the credit note.'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $params['contact_id'] = [
    'title' => ts('Contact Id'),
    'description' => ts('Internal CiviCRM contact ID. Either contact_id or contact_external_id is required.'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['contact_external_id'] = [
    'title' => ts('Contact External Id'),
    'description' => ts('External identifier of the contact (civicrm_contact.external_identifier). Used when contact_id is empty.'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['owner_organization_id'] = [
    'title' => ts('Owner Organization Id'),
    'description' => ts('Internal CiviCRM contact ID of the owning organisation. Either owner_organization_id or owner_organization_external_id is required.'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['owner_organization_external_id'] = [
    'title' => ts('Owner Organization External Id'),
    'description' => ts('External identifier of the owning organisation (civicrm_contact.external_identifier).'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['cn_number'] = [
    'title' => ts('Credit Note Number'),
    'description' => ts('Optional pre-set credit note number. If empty, the system generates one using the owner organisation prefix.'),
    'type' => CRM_Utils_Type::T_STRING,
    'maxlength' => 11,
  ];
  $params['date'] = [
    'title' => ts('Credit Note Date'),
    'description' => ts('Defaults to today if empty.'),
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $params['reference'] = [
    'title' => ts('Credit Note Reference'),
    'type' => CRM_Utils_Type::T_STRING,
    'maxlength' => 11,
  ];
  $params['currency'] = [
    'title' => ts('Currency'),
    'description' => ts('3-letter currency code (e.g. USD, GBP).'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'maxlength' => 3,
  ];
  $params['description'] = [
    'title' => ts('Credit Note Description'),
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $params['comment'] = [
    'title' => ts('Credit Note Comment'),
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $params['line_description'] = [
    'title' => ts('Line Description'),
    'description' => ts('Description of this line item.'),
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $params['line_quantity'] = [
    'title' => ts('Line Quantity'),
    'description' => ts('Defaults to 1 if empty.'),
    'type' => CRM_Utils_Type::T_FLOAT,
  ];
  $params['line_unit_price'] = [
    'title' => ts('Line Unit Price'),
    'description' => ts('Unit price for this line. Tax is added on top automatically when the financial type has a Sales Tax account configured - it is not a separate CSV column.'),
    'type' => CRM_Utils_Type::T_MONEY,
    'api.required' => 1,
  ];
  $params['line_financial_type'] = [
    'title' => ts('Line Financial Type'),
    'description' => ts('Name of the financial type for this line. The first line of a credit note also determines the credit-note-level financial type used for the Accounts Receivable accounting entry. Tax is derived from this financial type\'s Sales Tax Account relationship.'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
}
