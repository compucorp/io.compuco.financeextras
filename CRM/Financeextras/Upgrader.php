<?php

use Civi\Financeextras\Setup\Manage\CreditNoteStatusManager;
use Civi\Financeextras\Setup\Manage\CreditNoteActivityTypeManager;
use Civi\Financeextras\Setup\Manage\CreditNoteAllocationTypeManager;
use Civi\Financeextras\Setup\Manage\CreditNoteInvoiceTemplateManager;
use Civi\Financeextras\Setup\Manage\ContributionOwnerOrganizationManager;
use Civi\Financeextras\Setup\Configure\SetDefaultCompany;
use Civi\Financeextras\Setup\Manage\CreditNotePaymentInstrumentManager;
use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;

/**
 * Collection of upgrade steps.
 */
class CRM_Financeextras_Upgrader extends CRM_Financeextras_Upgrader_Base {

  /**
   * Tasks to perform when the extension is installed.
   */
  public function postInstall() {
    $manageSteps = [
      new CreditNoteStatusManager(),
      new CreditNoteActivityTypeManager(),
      new CreditNoteAllocationTypeManager(),
      new CreditNoteInvoiceTemplateManager(),
      new ContributionOwnerOrganizationManager(),
      new CreditNotePaymentInstrumentManager(),
      new AccountsReceivablePaymentMethod(),
    ];
    foreach ($manageSteps as $manageStep) {
      $manageStep->create();
    }

    $configurationSteps = [
      new SetDefaultCompany(),
    ];
    foreach ($configurationSteps as $configurationStep) {
      $configurationStep->apply();
    }
  }

  /**
   * Tasks to perform when the extension is uninstalled.
   */
  public function uninstall() {
    $steps = [
      new CreditNoteStatusManager(),
      new CreditNoteActivityTypeManager(),
      new CreditNoteAllocationTypeManager(),
      new CreditNoteInvoiceTemplateManager(),
      new ContributionOwnerOrganizationManager(),
      new CreditNotePaymentInstrumentManager(),
      new AccountsReceivablePaymentMethod(),
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
      new CreditNoteActivityTypeManager(),
      new CreditNoteAllocationTypeManager(),
      new CreditNoteInvoiceTemplateManager(),
      new ContributionOwnerOrganizationManager(),
      new CreditNotePaymentInstrumentManager(),
      new AccountsReceivablePaymentMethod(),
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
      new CreditNoteActivityTypeManager(),
      new CreditNoteAllocationTypeManager(),
      new CreditNoteInvoiceTemplateManager(),
      new ContributionOwnerOrganizationManager(),
      new CreditNotePaymentInstrumentManager(),
      new AccountsReceivablePaymentMethod(),
    ];

    foreach ($steps as $step) {
      $step->disable();
    }
  }

  public function upgrade_1000() {
    $this->executeSqlFile('sql/auto_install.sql');
    $this->executeCustomDataFile('xml/customFields_install.xml');

    $manageSteps = [
      new CreditNoteStatusManager(),
      new CreditNoteActivityTypeManager(),
      new CreditNoteAllocationTypeManager(),
      new CreditNoteInvoiceTemplateManager(),
      new ContributionOwnerOrganizationManager(),
      new CreditNotePaymentInstrumentManager(),
      new AccountsReceivablePaymentMethod(),
    ];
    foreach ($manageSteps as $manageStep) {
      $manageStep->create();
    }

    $configurationSteps = [
      new SetDefaultCompany(),
    ];
    foreach ($configurationSteps as $configurationStep) {
      $configurationStep->apply();
    }

    return TRUE;
  }

}
