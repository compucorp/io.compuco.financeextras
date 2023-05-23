<?php

use Civi\Financeextras\Setup\Manage\CreditNoteStatusManager;

/**
 * Collection of upgrade steps.
 */
class CRM_Financeextras_Upgrader extends CRM_Financeextras_Upgrader_Base {

  /**
   * Tasks to perform when the extension is installed.
   */
  public function install() {
    $steps = [
      new CreditNoteStatusManager(),
    ];

    foreach ($steps as $step) {
      $step->create();
    }
  }

  /**
   * Tasks to perform when the extension is uninstalled.
   */
  public function uninstall() {
    $steps = [
      new CreditNoteStatusManager(),
    ];

    foreach ($steps as $step) {
      $step->remove();
    }
  }

  /**
   * Tasks to perform when the extension is enanled.
   */
  public function enable() {
    $steps = [
      new CreditNoteStatusManager(),
    ];

    foreach ($steps as $step) {
      $step->enable();
    }
  }

  /**
   * Tasks to perform when the extension is disabled.
   */
  public function disable() {
    $steps = [
      new CreditNoteStatusManager(),
    ];

    foreach ($steps as $step) {
      $step->disable();
    }
  }

}
