<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n

$options = [];

return [
  'js' => [
    'js/strftime.js',
    'ang/fe-exchange-rate.module.js',
    'ang/fe-exchange-rate/*.js',
    'ang/fe-exchange-rate/*/*.js',
  ],
  'partials' => [
    'ang/fe-exchange-rate',
  ],
  'requires' => [
    'api4',
    'crmUi',
    'crmUtil',
    'ngRoute',
    'afsearchExchangeRate',
  ],
  'settings' => $options,
];
