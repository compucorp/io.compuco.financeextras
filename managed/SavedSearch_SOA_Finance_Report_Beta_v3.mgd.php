<?php

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
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_FinancialTrxn_FinancialAccount_to_financial_account_id_01.accounting_code',
            'FinancialItem_FinancialAccount_financial_account_id_01.accounting_code',
            'LOWER(FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_date) AS LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_trxn_date',
            'contact_id',
            'contact_id.display_name',
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.amount',
            'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_type_code',
            'FinancialItem_LineItem_entity_id_01_LineItem_Membership_entity_id_01.start_date',
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.trxn_id',
            'result_row_num',
            'LOWER(FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.total_amount) AS LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_total_amount',
            'LOWER(FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.amount) AS LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount',
            'FinancialItem_Contact_contact_id_01.display_name',
            'FinancialItem_Contact_contact_id_01.id',
            'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.id',
          ],
          'orderBy' => [],
          'where' => [],
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
            [
              'Contact AS FinancialItem_Contact_contact_id_01',
              'LEFT',
              [
                'contact_id',
                '=',
                'FinancialItem_Contact_contact_id_01.id',
              ],
            ],
            [
              'FinancialAccount AS FinancialItem_FinancialAccount_financial_account_id_01',
              'INNER',
              [
                'financial_account_id',
                '=',
                'FinancialItem_FinancialAccount_financial_account_id_01.id',
              ],
              [
                'OR',
                [
                  [
                    'FinancialItem_FinancialAccount_financial_account_id_01.is_tax',
                    'IS EMPTY',
                  ],
                  [
                    'FinancialItem_FinancialAccount_financial_account_id_01.is_tax',
                    '=',
                    FALSE,
                  ],
                ],
              ],
            ],
            [
              'FinancialAccount AS FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_FinancialTrxn_FinancialAccount_to_financial_account_id_01',
              'LEFT',
              [
                'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.to_financial_account_id',
                '=',
                'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_FinancialTrxn_FinancialAccount_to_financial_account_id_01.id',
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
              'LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_trxn_date',
              'DESC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_total_amount',
              'dataType' => 'String',
              'label' => 'Type',
              'sortable' => TRUE,
              'rewrite' => '{if [LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_total_amount] >= 0} BR {else}  BP {/if}',
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_FinancialTrxn_FinancialAccount_to_financial_account_id_01.accounting_code',
              'dataType' => 'String',
              'label' => 'Account Reference',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_FinancialAccount_financial_account_id_01.accounting_code',
              'dataType' => 'String',
              'label' => 'Nominal A/C Ref',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_trxn_date',
              'dataType' => 'String',
              'label' => 'Transaction Date',
              'sortable' => TRUE,
              'rewrite' => "{'[LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_trxn_date]'|date_format:\"%d/%m/%Y\"}",
            ],
            [
              'type' => 'field',
              'key' => 'result_row_num',
              'dataType' => 'Integer',
              'label' => 'Reference',
              'sortable' => FALSE,
              'rewrite' => 'CiviCRM',
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_Contact_contact_id_01.id',
              'dataType' => 'Integer',
              'label' => 'Member Number',
              'sortable' => TRUE,
              'rewrite' => '[FinancialItem_Contact_contact_id_01.id]',
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'FinancialItem_Contact_contact_id_01',
                'target' => '_blank',
              ],
              'title' => 'View Financial Item Contact',
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_EntityFinancialTrxn_FinancialTrxn_01.amount',
              'dataType' => 'Money',
              'label' => 'Net Amount',
              'sortable' => TRUE,
              'rewrite' => '{if [LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount] >= 0} [LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount] {else}  {math equation="x * y" x=[LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount] y=-1 format="%.2f"} {/if}',
            ],
            [
              'type' => 'field',
              'key' => 'FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_type_code',
              'dataType' => 'String',
              'label' => 'Tax Code',
              'sortable' => TRUE,
              'rewrite' => '[FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_type_code]',
            ],
            [
              'type' => 'field',
              'key' => 'LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount',
              'dataType' => 'String',
              'label' => 'Tax Amount',
              'sortable' => TRUE,
              'rewrite' => "{if '[FinancialItem_LineItem_entity_id_01_LineItem_FinancialType_financial_type_id_01_FinancialType_EntityFinancialAccount_FinancialAccount_01.account_type_code]' == \"T8\"} {if [LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount] >= 0} {math equation=\"x * y\" x=[LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount] y=0.2} {else}  {math equation=\"x * y * z\" x=[LOWER_FinancialItem_EntityFinancialTrxn_FinancialTrxn_01_amount] y=0.2 z=-1} {/if}  {else} 0 {/if}",
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
          ],
          'button' => NULL,
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];
$searchKitIsInstalled = 'installed' ===
CRM_Extension_System::singleton()->getManager()->getStatus('org.civicrm.search_kit');
if ($searchKitIsInstalled) {
  return $mgd;
}

return [];
