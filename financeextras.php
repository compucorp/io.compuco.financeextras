<?php

require_once 'financeextras.civix.php';
// phpcs:disable

use CRM_Financeextras_ExtensionUtil as E;
use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;
use Civi\Financeextras\Hook\AlterMailParams\AlterContributionReceipt;

// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function financeextras_civicrm_config(&$config) {
  _financeextras_civix_civicrm_config($config);
  Civi::dispatcher()->addListener('civi.api.respond', ['Civi\Financeextras\APIWrapper\SearchDisplayRun', 'respond'], -100);
  Civi::dispatcher()->addSubscriber(new Civi\Financeextras\Event\Subscriber\CreditNoteInvoiceSubscriber());
  Civi::dispatcher()->addListener('civi.api.respond', ['Civi\Financeextras\APIWrapper\Contribution', 'respond'], -101);
  Civi::dispatcher()->addListener('fe.contribution.received_payment', ['\Civi\Financeextras\Event\Listener\ContributionPaymentUpdatedListener', 'handle']);
  Civi::dispatcher()->addListener('civi.api.prepare', ['Civi\Financeextras\APIWrapper\BatchListPage', 'preApiCall']);
  Civi::dispatcher()->addListener('civi.token.list', 'financeextras_register_tokens');
}

/**
 * Implements hook_civicrm_container().
 */
function financeextras_civicrm_container($container) {
  $containers = [
    new \Civi\Financeextras\Hook\Container\ServiceContainer($container),
  ];

  foreach ($containers as $container) {
    $container->register();
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function financeextras_civicrm_install() {
  _financeextras_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function financeextras_civicrm_postInstall() {
  _financeextras_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function financeextras_civicrm_uninstall() {
  _financeextras_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function financeextras_civicrm_enable() {
  _financeextras_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function financeextras_civicrm_disable() {
  _financeextras_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function financeextras_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _financeextras_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function financeextras_civicrm_entityTypes(&$entityTypes) {
  _financeextras_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_pageRun().
 *
 * @link https://docs.civicrm.org/dev/en/master/hooks/hook_civicrm_pageRun/
 */
function financeextras_civicrm_pageRun($page) {
  $hooks = [
    CRM_Financeextras_Hook_PageRun_ContributionPageTab::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($page)) {
      (new $hook())->handle($page);
    }
  }
}

/**
 * Implements hook_civicrm_links().
 */
function financeextras_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  $hooks = [
    \Civi\Financeextras\Hook\Links\Contribution::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($op, $objectName)) {
      (new $hook($op, $objectId, $objectName, $links))->handle();
    }
  }
}

/**
 * Implements hook_civicrm_tabset().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_tabset
 */
function financeextras_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName === 'civicrm/contact/view') {
    $loader = Civi::service('angularjs.loader');
    $loader->addModules(['crmApp', 'fe-creditnote']);
  }
}

function financeextras_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName === 'CreditNoteAllocation' && in_array($op, ['create', 'edit'])) {
    \CRM_Financeextras_BAO_CreditNote::updateCreditNoteStatusPostAllocation($objectId);
  }

  if ($objectName === 'Contribution' && $op === 'create') {
    $hook = new \Civi\Financeextras\Hook\Post\ContributionCreation($objectId);
    $hook->run();
  }

  if ($objectName === 'Contribution' && in_array($op, ['create', 'edit'])) {
    \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($objectId));
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $objectId)
      ->execute()
      ->first();
    if (empty($objectRef->contact_id)) {
      $objectRef->contact_id = $contribution['contact_id'];
    }

    (new \Civi\Financeextras\Hook\Post\UpdateContributionExchangeRate($objectId))->run();
  }
}

/**
 * Implements fieldOptions hook().
 *
 * @param string $entity
 * @param string $field
 * @param array $options
 * @param array $params
 */
function financeextras_civicrm_fieldOptions($entity, $field, &$options, $params) {
  if (in_array($entity, ['FinancialItem']) && $field == 'entity_table') {
    $options[\CRM_Financeextras_DAO_CreditNoteLine::$_tableName] = ts('Credit Note Line');
  }

  if (in_array($entity, ['EntityFinancialTrxn']) && $field == 'entity_table') {
    $options[\CRM_Financeextras_DAO_CreditNote::$_tableName] = ts('Credit Note');
    $options[\CRM_Financeextras_DAO_CreditNoteLine::$_tableName] = ts('Credit Note Line');
  }
}

/**
 *
 * Implements hook_civicrm_validateForm().
 *
 */
function financeextras_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  $hooks = [
    \Civi\Financeextras\Hook\ValidateForm\MembershipCreate::class,
    \Civi\Financeextras\Hook\ValidateForm\ContributionEdit::class,
    \Civi\Financeextras\Hook\ValidateForm\ContributionCreate::class,
    \Civi\Financeextras\Hook\ValidateForm\OwnerOrganizationValidator::class,
    \Civi\Financeextras\Hook\ValidateForm\PriceSetValidator::class,
    \Civi\Financeextras\Hook\ValidateForm\FinancialTypeAccount::class,
    \Civi\Financeextras\Hook\ValidateForm\LineItemEdit::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($form, $formName)) {
      (new $hook($form, $fields, $errors, $formName))->handle();
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 */
function financeextras_civicrm_postProcess($formName, $form) {
  $hooks = [
    \Civi\Financeextras\Hook\PostProcess\ParticipantPostProcess::class,
    \Civi\Financeextras\Hook\PostProcess\LocalizationPostProcess::class,
    \Civi\Financeextras\Hook\PostProcess\ContributionPostProcess::class,
    \Civi\Financeextras\Hook\PostProcess\AdditionalPaymentPostProcess::class,
    \Civi\Financeextras\Hook\PostProcess\FinancialBatchPostProcess::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($form, $formName)) {
      (new $hook($form))->handle();
    }
  }
}

/**
 * Implements hook_civicrm_buildForm().
 */
function financeextras_civicrm_buildForm($formName, &$form) {
  $hooks = [
    \Civi\Financeextras\Hook\BuildForm\ContributionView::class,
    \Civi\Financeextras\Hook\BuildForm\MembershipCreate::class,
    \Civi\Financeextras\Hook\BuildForm\ParticipantCreate::class,
    \Civi\Financeextras\Hook\BuildForm\ContributionCreate::class,
    \Civi\Financeextras\Hook\BuildForm\FinancialBatch::class,
    \Civi\Financeextras\Hook\BuildForm\BatchTransaction::class,
    \Civi\Financeextras\Hook\BuildForm\FinancialBatchSearch::class,
    \Civi\Financeextras\Hook\BuildForm\FinancialAccount::class,
    \Civi\Financeextras\Hook\BuildForm\AdditionalPaymentButton::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($form, $formName)) {
      (new $hook($form))->handle();
    }
  }
}

/**
 * Implements hook_civicrm_alterMailParams().
 */
function financeextras_civicrm_alterMailParams(&$params, $context) {
  $hooks = [
    AlterContributionReceipt::class,
    \Civi\Financeextras\Hook\AlterMailParams\InvoiceTemplate::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($params, $context)) {
      (new $hook($params, $context))->handle();
    }
  }
}

/**
 * Implements hook_civicrm_alterMailContent().
 */
function financeextras_civicrm_alterMailContent(&$content) {
  if (($content['workflow_name'] ?? NULL) === 'contribution_offline_receipt') {
    $content['html'] = str_replace('$formValues.total_amount', '$contribution.total_amount', $content['html']);
  }

  if (($content['workflow_name'] ?? NULL) === 'contribution_invoice_receipt') {
    $path = E::path('/templates/CRM/Financeextras/MessageTemplate/SalesTaxConversionRateTable.tpl');
    $content['html'] = str_replace('{contribution.tax_exchange_rate_table}', file_get_contents($path), $content['html']);
  }
}

/**
 * Add financeextras tokens
 */
function financeextras_register_tokens(\Civi\Token\Event\TokenRegisterEvent $e) {
  $e->entity('contribution')->register('tax_exchange_rate_table', ts('Tax exchange rate table'));
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu/
 */
function financeextras_civicrm_navigationMenu(&$menu) {
  $companyMenuItem = [
    'name' => 'financeextras_company',
    'label' => ts('Companies (For Multi-company accounting)'),
    'url' => 'civicrm/admin/financeextras/company',
    'permission' => 'administer CiviCRM',
    'separator' => 2,
  ];

  _financeextras_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', $companyMenuItem);

  _financeextras_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', [
    'label' => E::ts('Currency Exchange Settings'),
    'name' => 'financeextras_exchangerate_settings',
    'url' => 'civicrm/admin/setting/exchange-rate',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  _financeextras_civix_insert_navigation_menu($menu, 'Administer/CiviContribute', [
    'label' => E::ts('Exchange Rates'),
    'name' => 'financeextras_exchangerate_settings',
    'url' => 'civicrm/exchange-rate',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _financeextras_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_alterContent().
 */
function financeextras_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  if ($tplName == 'CRM/Financial/Page/BatchTransaction.tpl') {
    $hook = new \Civi\Financeextras\Hook\AlterContent\BatchTransaction($content);
    $hook->run();
  }
}

/**
 * Implements hook_civicrm_selectWhereClause().
 */
function financeextras_civicrm_selectWhereClause($entity, &$clauses) {
  $ownerOrganisationToFilterIds = CRM_Utils_Request::retrieve('financeextras_owner_org_id', 'CommaSeparatedIntegers');
  if ($entity == 'Batch' && !empty($ownerOrganisationToFilterIds)) {
    $hook = new \Civi\Financeextras\Hook\SelectWhereClause\BatchList($clauses);
    $hook->filterBasedOnOwnerOrganisations($ownerOrganisationToFilterIds);
  }
}
