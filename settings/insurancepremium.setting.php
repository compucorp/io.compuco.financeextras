<?php
return [
  'insurance_premium_financial_type' => [
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'insurance_premium_financial_type',
    'html_type' => 'entity_reference',
    'add' => '5.51.3',
    'type' => 'String',
    'title' => ts('Financial Type for insurance premium certificate tokens'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => '',
    'default' => '',
    'help_text' => '',
    'settings_pages' => ['contribute' => ['weight' => 15]],
    'entity_reference_options' => [
      'entity' => 'FinancialType',
      'select' => ['minimumInputLength' => 0],
      'multiple' => TRUE,
    ],
  ],
];
