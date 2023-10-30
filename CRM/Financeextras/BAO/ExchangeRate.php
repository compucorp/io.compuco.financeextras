<?php
use CRM_Financeextras_ExtensionUtil as E;

class CRM_Financeextras_BAO_ExchangeRate extends CRM_Financeextras_DAO_ExchangeRate {

  /**
   * Create a new ExchangeRate based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_ExchangeRate|NULL
   *
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_ExchangeRate';
    $entityName = 'ExchangeRate';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

}
