<?php

class CRM_Financeextras_BAO_BatchOwnerOrganisation extends CRM_Financeextras_DAO_BatchOwnerOrganisation {

  /**
   * Create a new Batch-Owner Organisation record based on array-data
   *
   * @param array $params
   * @return CRM_Financeextras_DAO_BatchOwnerOrganisation|NULL
   */
  public static function create($params) {
    $entityName = 'BatchOwnerOrganisation';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new CRM_Financeextras_DAO_BatchOwnerOrganisation();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Gets all the owner Organisation records
   * for a given batch.
   *
   * @param int $batchId
   * @return array
   */
  public static function getByBatchId($batchId) {
    $records = new CRM_Financeextras_DAO_BatchOwnerOrganisation();
    $records->batch_id = $batchId;
    $records->find();

    $ownerOrgIds = [];
    while ($records->fetch()) {
      $ownerOrgIds[] = $records->owner_org_id;
    }

    return $ownerOrgIds;
  }

  /**
   * Deletes all the owner Organisation records
   * for a given batch.
   *
   * @param int $batchId
   * @return void
   */
  public static function deleteByBatchId($batchId) {
    $record = new CRM_Financeextras_DAO_BatchOwnerOrganisation();
    $record->batch_id = $batchId;
    $record->delete();
  }

}
