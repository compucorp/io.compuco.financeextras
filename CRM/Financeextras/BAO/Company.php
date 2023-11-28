<?php

class CRM_Financeextras_BAO_Company extends CRM_Financeextras_DAO_Company {

  /**
   * Create a new Company based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_Company|NULL
   */
  public static function create($params) {
    $entityName = 'Company';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new CRM_Financeextras_DAO_Company();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  public static function getById($id) {
    $record = new CRM_Financeextras_DAO_Company();
    $record->id = $id;
    $record->find(TRUE);

    return $record;
  }

  public static function deleteById($id) {
    $record = new CRM_Financeextras_DAO_Company();
    $record->id = $id;
    $record->delete();
  }

}
