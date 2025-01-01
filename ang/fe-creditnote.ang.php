<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n

return [
  'js' => [
    'js/strftime.js',
    'ang/fe-creditnote.module.js',
    'ang/fe-creditnote/*.js',
    'ang/fe-creditnote/*/*.js',
  ],
  'css' => [
    'css/fe-creditnote.css',
  ],
  'partials' => [
    'ang/fe-creditnote',
  ],
  'requires' => [
    'api4',
    'crmUi',
    'crmUtil',
    'ngRoute',
    'afsearchCreditNotes',
  ],
  'settingsFactory' => ['CRM_Financeextras_Settings', 'getAll'],
];
