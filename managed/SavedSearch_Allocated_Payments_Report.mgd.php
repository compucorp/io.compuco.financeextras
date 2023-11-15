<?php

/**
 * @file
 * Exported creditnotes saved search.
 */

$mgd = [
  [
    'name' => 'SavedSearch_SOA_Finance_Report_Beta_v3',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SOA_Finance_Report_Beta_v3',
        'label' => 'SOA Finance Report Beta v3',
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'FinancialItem',
        'api_params' => [
          'version' => 4,
          'select' => [
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.to_financial_account_id:label',
            'financial_account_id:label',
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_date',
            'contact_id',
            'contact_id.display_name',
            'ABS(FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.amount) AS ABS_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount',
            'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_type_code',
            'FinancialItem_LineItem_entity_id_01_LineItem_Membership_entity_id_01.start_date',
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_id',
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.total_amount',
          ],
          'orderBy' => [],
          'where' => [
            [
              'description',
              'NOT REGEXP',
              'VAT',
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'FinancialTrxn AS FinancialItem_EntityFinancialTrxn_FinancialTrxn_01',
              'INNER',
              'EntityFinancialTrxn',
              [
                'id',
                '=',
                'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.entity_id',
              ],
              [
                'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.entity_table',
                '=',
                "'civicrm_financial_item'",
              ],
              [
                'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.is_payment',
                '=',
                TRUE,
              ],
            ],
            [
              'LineItem AS FinancialItem_LineItem_entity_id_01',
              'INNER',
              [
                'entity_id',
                '=',
                'FinancialItem_LineItem_entity_id_01.id',
              ],
              [
                'entity_table',
                '=',
                "'civicrm_line_item'",
              ],
            ],
            [
              'FinancialType AS FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01',
              'INNER',
              [
                'FinancialItem_LineItem_entity_id_01.financial_type_id',
                '=',
                'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01.id',
              ],
            ],
            [
              'FinancialAccount AS FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01',
              'LEFT',
              'EntityFinancialAccount',
              [
                'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01.id',
                '=',
                'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.entity_id',
              ],
              [
                'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.entity_table',
                '=',
                "'civicrm_financial_type'",
              ],
              [
                'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_relationship:name',
                '=',
                '"Sales Tax Account is"',
              ],
            ],
            [
              'Membership AS FinancialItem_LineItem_entity_id_01_LineItem_Membership_entity_id_01',
              'LEFT',
              [
                'FinancialItem_LineItem_entity_id_01.entity_id',
                '=',
                'FinancialItem_LineItem_entity_id_01_LineItem_Membership_entity_id_01.id',
              ],
              [
                'FinancialItem_LineItem_entity_id_01.entity_table',
                '=',
                "'civicrm_membership'",
              ],
            ],
          ],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
        'mapping_id' => NULL,
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_SOA_Finance_Report_Beta_v3_SearchDisplay_SOA_Finance_Report_Beta_v3_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SOA_Finance_Report_Beta_v3_Table_1',
        'label' => 'SOA Finance Report Beta v3 Table 1',
        'saved_search_id.name' => 'SOA_Finance_Report_Beta_v3',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [],
          'placeholder' => 5,
          'sort' => [
            [
              'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_date',
              'DESC',
            ],
          ],
          'columns' => [
            [
              'path' => '',
              'type' => 'include',
              'alignment' => '',
              'label' => 'Type',
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.to_financial_account_id:label',
              'dataType' => 'Integer',
              'label' => 'Account Reference',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'financial_account_id:label',
              'dataType' => 'Integer',
              'label' => 'Nominal A/C Ref',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_date',
              'dataType' => 'Timestamp',
              'label' => 'Transaction Date',
              'sortable' => TRUE,
            ],
            [
              'path' => '',
              'type' => 'include',
              'alignment' => '',
              'label' => 'Reference',
            ],
            [
              'type' => 'field',
              'key' => 'contact_id',
              'dataType' => 'Integer',
              'label' => 'Member Number',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contact_id.display_name',
              'dataType' => 'String',
              'label' => 'Contact Name',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'ABS_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount',
              'dataType' => 'Integer',
              'label' => 'Net Amount',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_type_code',
              'dataType' => 'String',
              'label' => 'Tax Code',
              'sortable' => TRUE,
            ],
            [
              'path' => '',
              'type' => 'include',
              'alignment' => '',
              'label' => 'Tax Amount',
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_LineItem_entity_id_01_LineItem_Membership_entity_id_01.start_date',
              'dataType' => 'Date',
              'label' => 'Start Date',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_id',
              'dataType' => 'String',
              'label' => 'Stripe Transaction ID',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.total_amount',
              'dataType' => 'Money',
              'label' => 'Financial Item Financial Trxns: Financial Total Amount',
              'sortable' => TRUE,
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];;

$searchKitIsInstalled = 'installed' ===
CRM_Extension_System::singleton()->getManager()->getStatus('org.civicrm.search_kit');
if ($searchKitIsInstalled) {
  return $mgd;
}

return [];
