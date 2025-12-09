<?php

use CRM_Financeextras_ExtensionUtil as E;

return [
  'financeextras_enable_overpayments' => [
    'name' => 'financeextras_enable_overpayments',
    'type' => 'Boolean',
    'default' => FALSE,
    'html_type' => 'checkbox',
    'title' => E::ts('Enable Overpayments'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('Enable this setting to allow users to create credit notes for overpayments. Suitable financial types for overpayments need to be configured on your company record(s).'),
    'help_text' => E::ts('Enable this setting to allow users to create credit notes for overpayments. Configure the overpayment financial type on each company record.'),
    'settings_pages' => ['contribute' => ['weight' => 15]],
  ],
];
