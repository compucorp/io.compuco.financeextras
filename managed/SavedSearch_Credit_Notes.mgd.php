<?php

/**
 * @file
 * Exported creditnotes saved search.
 */

$mgd = [
  [
    'name' => 'SavedSearch_Credit_Notes_List',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Credit_Notes_List',
        'label' => 'Credit Notes List',
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'CreditNote',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'cn_number',
            'status_id:label',
            'reference',
            'date',
            'total_credit',
            'SUM(CreditNote_CreditNoteAllocation_credit_note_id_01.amount) AS SUM_CreditNote_CreditNoteAllocation_credit_note_id_01_amount',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'CreditNoteAllocation AS CreditNote_CreditNoteAllocation_credit_note_id_01',
              'LEFT',
              [
                'id',
                '=',
                'CreditNote_CreditNoteAllocation_credit_note_id_01.credit_note_id',
              ],
              [
                'CreditNote_CreditNoteAllocation_credit_note_id_01.is_reversed',
                '=',
                FALSE,
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
    'name' => 'SavedSearch_Credit_Notes_List_SearchDisplay_Credit_Notes_List_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Credit_Notes_List_Table_1',
        'label' => 'Credit Notes List Table 1',
        'saved_search_id.name' => 'Credit_Notes_List',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'cn_number',
              'dataType' => 'String',
              'label' => 'Number',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'dataType' => 'Integer',
              'label' => 'Status',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'reference',
              'dataType' => 'String',
              'label' => 'Reference',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'date',
              'dataType' => 'Date',
              'label' => 'Date',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'total_credit',
              'dataType' => 'Money',
              'label' => 'Total Value',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'SUM_CreditNote_CreditNoteAllocation_credit_note_id_01_amount',
              'dataType' => 'Money',
              'label' => 'Allocated',
              'sortable' => TRUE,
              'empty_value' => '0.00',
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-sm',
              'icon' => 'fa-ellipsis-v',
              'links' => [
                [
                  'entity' => 'CreditNote',
                  'action' => 'view',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-external-link',
                  'text' => 'View',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'CreditNote',
                  'action' => 'update',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-pencil',
                  'text' => 'Edit',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'path' => 'civicrm/contribution/creditnote/download-pdf?id=[id]',
                  'icon' => 'fa-download',
                  'text' => 'Download PDF Document Credit Note',
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'path' => 'civicrm/contribution/creditnote/email?id=[id]',
                  'icon' => 'fa-envelope-o',
                  'text' => 'Email Credit Note',
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'path' => 'civicrm/contribution/creditnote/void?id=[id]',
                  'icon' => 'fa-ban',
                  'text' => 'Void',
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'path' => 'civicrm/contribution/creditnote/allocate?crid=[id]',
                  'icon' => 'fa-money',
                  'text' => 'Allocate credit to invoice',
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'path' => 'civicrm/contribution/creditnote/refund?id=[id]',
                  'icon' => 'fa-retweet',
                  'text' => 'Record cash refund',
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'path' => 'civicrm/',
                  'icon' => 'fa-credit-card',
                  'text' => 'Make credit card refund',
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'entity' => 'CreditNote',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => 'Delete',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [
                    'check user permission',
                    '=',
                    'delete in CiviContribute',
                  ],
                ],
              ],
              'type' => 'menu',
              'alignment' => '',
              'label' => 'Actions',
            ],
          ],
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
