<?php

/**
 * @file
 * Declares an Angular module which can be autoloaded in CiviCRM.
 *
 * See also:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules.
 */

$requires = [
  'api4',
  'crmUi',
  'crmUtil',
  'ngRoute',
  'afsearchAllocatedPaymentsReport',
];

return [
  'js' => [
    'ang/allocated-payments.module.js',
    'ang/allocated-payments/*.js',
    'ang/allocated-payments/*/*.js',
  ],
  'requires' => $requires,
  'partials' => [
    'ang/allocated-payments',
  ],
];
