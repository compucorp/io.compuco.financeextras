<?php

require_once 'financeextras.civix.php';
// phpcs:disable
use CRM_Financeextras_ExtensionUtil as E;
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
  if (CRM_Financeextras_Hook_Links_Contribution::shouldHandle($op, $objectName)) {
    $contributionHook = new CRM_Financeextras_Hook_Links_Contribution($objectId, $links);
    $contributionHook->alterLinks();
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
  }
}

/*
 * Implements hook_civicrm_validateForm().
 */
function financeextras_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  $hooks = [
    \Civi\Financeextras\Hook\ValidateForm\MembershipCreate::class,
    \Civi\Financeextras\Hook\ValidateForm\ContributionCreate::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($form, $formName)) {
      (new $hook($form, $fields, $errors))->handle();
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 */
function financeextras_civicrm_postProcess($formName, $form) {
  $hooks = [
    \Civi\Financeextras\Hook\PostProcess\ParticipantPostProcess::class,
    \Civi\Financeextras\Hook\PostProcess\ContributionPostProcess::class,
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
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($form, $formName)) {
      (new $hook($form))->handle();
    }
  }
}
