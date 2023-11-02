<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n

use Civi\Api4\OptionValue;
use Civi\Financeextras\Utils\CurrencyUtils;

$options = [
  'shortDateFormat' => Civi::Settings()->get('dateformatshortdate'),
  'canEditContribution' => CRM_Core_Permission::check('edit contributions'),
];

/**
 * Exposes currency codes to Angular.
 */
function financeextras_set_currency_codes(&$options) {
  $options['currencyCodes'] = CurrencyUtils::getCurrencies();
}

/**
 * Exposes credit note statuses to Angular.
 */
function financeextras_set_credit_note_status(&$options) {
  $optionValues = OptionValue::get()
    ->addSelect('id', 'value', 'name', 'label')
    ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_status')
    ->execute();

  $options['creditNoteStatus'] = $optionValues->getArrayCopy();
}

/**
 * Exposes credit note statuses to Angular.
 */
function financeextras_set_companies(&$options) {
  $options['companies'] = \Civi\Api4\Company::get(FALSE)
    ->addSelect('contact_id.organization_name', 'contact_id')
    ->execute()
    ->getArrayCopy();
}

financeextras_set_currency_codes($options);
financeextras_set_credit_note_status($options);
financeextras_set_companies($options);

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
  'settings' => $options,
];
