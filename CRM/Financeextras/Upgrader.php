<?php

use Civi\Financeextras\Setup\Configure\SetDefaultCompany;
use Civi\Financeextras\Setup\Manage\CreditNoteStatusManager;
use Civi\Financeextras\Setup\Manage\ExchangeRateFieldManager;
use Civi\Financeextras\Setup\Manage\CreditNoteActivityTypeManager;
use Civi\Financeextras\Setup\Manage\CreditNoteAllocationTypeManager;
use Civi\Financeextras\Setup\Manage\CreditNoteInvoiceTemplateManager;
use Civi\Financeextras\Setup\Manage\CreditNotePaymentInstrumentManager;
use Civi\Financeextras\Setup\Manage\ContributionOwnerOrganizationManager;
use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;
use Civi\Financeextras\Service\IncompleteContributionFixService;

/**
 * Collection of upgrade steps.
 */
class CRM_Financeextras_Upgrader extends CRM_Extension_Upgrader_Base {

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
      new ExchangeRateFieldManager(),
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
      new ExchangeRateFieldManager(),
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
      new ExchangeRateFieldManager(),
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
    $this->executeSqlFile('sql/upgrade_1000.sql');
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

  /**
   * This upgrade updates creditnote line table
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001');
    $this->executeSqlFile('sql/upgrade_1001.sql');

    return TRUE;
  }

  /**
   * This upgrade updates company table
   */
  public function upgrade_1002() {
    $this->ctx->log->info('Applying update 1002');
    $this->executeSqlFile('sql/upgrade_1002.sql');

    $defaultAccountReceivableAccount = \Civi\Api4\OptionValue::get(FALSE)
      ->setCheckPermissions(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'payment_instrument')
      ->addWhere('name', '=', AccountsReceivablePaymentMethod::NAME)
      ->execute()
      ->first();

    if (!empty($defaultAccountReceivableAccount['value'])) {
      \Civi\Api4\Company::update(FALSE)
        ->addValue('receivable_payment_method', $defaultAccountReceivableAccount['value'])
        ->addWhere('id', '=', 1)
        ->execute();
    }

    return TRUE;
  }

  /**
   * This upgrade updates line items that have empty price field values
   */
  public function upgrade_1003() {
    $this->ctx->log->info('Applying update 1003');

    try {
      $priceSetId        = \CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_contribution_amount', 'id', 'name');
      $priceSet          = current(\CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
      $priceField      = NULL;
      foreach ($priceSet['fields'] as $field) {
        if ($field['name'] == 'contribution_amount') {
          $priceField = $field;
          break;
        }
      }

      if (empty($priceField)) {
        return TRUE;
      }
      $priceFieldValueID = current($priceField['options'])['id'] ?? NULL;
      if (empty($priceFieldValueID)) {
        return TRUE;
      }

      \Civi\Api4\LineItem::update(FALSE)
        ->addValue('price_field_id', $priceField['id'])
        ->addValue('price_field_value_id', $priceFieldValueID)
        ->addClause('OR', ['price_field_id', 'IS NULL'], ['price_field_value_id', 'IS NULL'])
        ->addWhere('contribution_id', 'IS NOT NULL')
        ->execute();

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->ctx->log->info($e->getMessage());

      return FALSE;
    }
  }

  /**
   * Executes upgrade 1004
   */
  public function upgrade_1004(): bool {
    try {
      $contributionFix = new IncompleteContributionFixService();
      $processedContributions = $contributionFix->execute();
      $this->ctx->log->info(json_encode($processedContributions));

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->ctx->log->info($e->getMessage());

      return FALSE;
    }
  }

}
