<?php
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Financeextras_Upgrader extends CRM_Financeextras_Upgrader_Base {

  /**
   * This upgrade creates financeextras_exchange_rate table
   */
  public function upgrade_0001() {
    $this->ctx->log->info('Applying update 0001');
    $this->executeSqlFile('sql/upgrade_0001.sql');

    return TRUE;
  }

}
