<?php
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Financeextras_Upgrader extends CRM_Financeextras_Upgrader_Base {

  /**
   * Executes upgrade 1001
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying Financeextras update 1001');
    $this->executeSqlFile('sql/upgrade/1001.sql');
    CRM_Utils_System::flushCache();

    return TRUE;
  }

}
