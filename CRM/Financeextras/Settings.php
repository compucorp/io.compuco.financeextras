<?php

use Civi\Api4\OptionValue;
use Civi\Api4\Company;
use Civi\Financeextras\Utils\CurrencyUtils;

/**
 * Get a list of settings for angular pages.
 */
class CRM_Financeextras_Settings {

  /**
   * Get a list of settings for angular pages.
   */
  public static function getAll(): array {
    $options = [
      'shortDateFormat' => Civi::Settings()->get('dateformatshortdate'),
      'canEditContribution' => CRM_Core_Permission::check('edit contributions'),
      'currencyCodes' => CurrencyUtils::getCurrencies(),
    ];

    $options['creditNoteStatus'] = OptionValue::get(FALSE)
      ->addSelect('id', 'value', 'name', 'label')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_status')
      ->execute()
      ->getArrayCopy();

    $options['companies'] = Company::get(FALSE)
      ->addSelect('contact_id.organization_name', 'contact_id')
      ->execute()
      ->getArrayCopy();

    return $options;
  }

}
