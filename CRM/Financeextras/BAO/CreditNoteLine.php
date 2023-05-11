<?php

class CRM_Financeextras_BAO_CreditNoteLine extends CRM_Financeextras_DAO_CreditNoteLine {

  /**
   * Create a new CreditNoteLine based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_CreditNoteLine|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_CreditNoteLine';
    $entityName = 'CreditNoteLine';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

}
