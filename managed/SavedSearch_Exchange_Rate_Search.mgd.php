<?php

$mgd = [
  [
    'name' => 'SavedSearch_Exchange_Rate_Search',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Exchange_Rate_Search',
        'label' => 'Exchange Rate Search',
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'ExchangeRate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'exchange_date',
            'base_currency:label',
            'conversion_currency:label',
            'UPPER(base_to_conversion_rate) AS UPPER_base_to_conversion_rate',
            'UPPER(conversion_to_base_rate) AS UPPER_conversion_to_base_rate',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
        'mapping_id' => NULL,
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Exchange_Rate_Search_SearchDisplay_Exchange_Rate_Search_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Exchange_Rate_Search_Table_1',
        'label' => 'Exchange Rate Search Table 1',
        'saved_search_id.name' => 'Exchange_Rate_Search',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [],
          'placeholder' => 5,
          'sort' => [
            [
              'base_currency:label',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'exchange_date',
              'dataType' => 'Date',
              'label' => 'Exchange Date',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'base_currency:label',
              'dataType' => 'String',
              'label' => 'Base Currency',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'conversion_currency:label',
              'dataType' => 'String',
              'label' => 'Conversion Currency',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'UPPER_base_to_conversion_rate',
              'dataType' => 'String',
              'label' => 'Base To Conversion Rate',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'UPPER_conversion_to_base_rate',
              'dataType' => 'String',
              'label' => 'Conversion To Base Rate',
              'sortable' => TRUE,
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-sm',
              'icon' => 'fa-ellipsis-v',
              'links' => [
                [
                  'entity' => 'ExchangeRate',
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
                  'entity' => 'ExchangeRate',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => 'Delete',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => '',
              'label' => 'Action',
            ],
          ],
          'noResultsText' => 'No Exchange rate value found',
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
