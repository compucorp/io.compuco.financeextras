<?php

namespace Civi\Financeextras\Utils;

use Civi\Api4\OptionValue;

/**
 * Class provide utility methods for 'OptionValue'
 */
class OptionValueUtils {

  /**
   * Gets the option value ID (value)
   * for the specified option group.
   *
   * @param string $optionGroupName
   * @param string $optionValueName
   *
   * @return string
   */
  public static function getValueForOptionValue($optionGroupName, $optionValueName) {
    return OptionValue::get()
      ->addWhere('option_group_id:name', '=', $optionGroupName)
      ->addWhere('name', '=', $optionValueName)
      ->addSelect('value')
      ->setLimit(1)
      ->execute()
      ->first()['value'] ?? NULL;
  }

}
