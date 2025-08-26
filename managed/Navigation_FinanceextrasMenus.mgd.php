<?php

/**
 * @file
 * Navigation menu items for Finance Extras extension.
 */

return [
  [
    'name' => 'Navigation_financeextras_company',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'financeextras_company',
        'label' => 'Companies',
        'url' => 'civicrm/admin/financeextras/company',
        'permission' => 'administer CiviCRM',
        'parent_id.name' => 'CiviContribute',
        'has_separator' => 2,
        'weight' => 100,
        'is_active' => 1,
      ],
    ],
  ],
  [
    'name' => 'Navigation_financeextras_exchangerate_list',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'financeextras_exchangerate_list',
        'label' => 'Exchange Rates',
        'url' => 'civicrm/exchange-rate',
        'permission' => 'administer CiviCRM',
        'parent_id.name' => 'CiviContribute',
        'has_separator' => 1,
        'weight' => 102,
        'is_active' => 1,
      ],
    ],
  ],
];
