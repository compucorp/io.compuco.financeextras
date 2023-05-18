<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n

use Civi\Financeextras\Utils\CurrencyUtils;

$options = [];

/**
 * Exposes currency codes to Angular.
 */
function financeextras_set_currency_codes(&$options) {
  $options['currencyCodes'] = CurrencyUtils::getCurrencies();
}

financeextras_set_currency_codes($options);

return [
  'js' => [
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
  'settings' => $options,
];
