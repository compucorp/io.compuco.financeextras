<?php

use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;

/**
 * Collection of upgrade steps.
 */
class CRM_Financeextras_Upgrader extends CRM_Financeextras_Upgrader_Base {

  public function install() {
    $creationSteps = [
      new AccountsReceivablePaymentMethod(),
    ];
    foreach ($creationSteps as $step) {
      $step->create();
    }
  }

  public function enable() {
    $steps = [
      new AccountsReceivablePaymentMethod(),
    ];
    foreach ($steps as $step) {
      $step->activate();
    }
  }

  public function disable() {
    $steps = [
      new AccountsReceivablePaymentMethod(),
    ];
    foreach ($steps as $step) {
      $step->deactivate();
    }
  }

  public function uninstall() {
    $removalSteps  = [
      new AccountsReceivablePaymentMethod(),
    ];
    foreach ($removalSteps as $step) {
      $step->remove();
    }
  }

}
