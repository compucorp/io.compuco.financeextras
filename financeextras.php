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
 * Implements hook_civicrm_links().
 */
function financeextras_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if (is_null($objectId)) {
    return;
  }

  $hooks = [
    new \Civi\Financeextras\Hook\Links\Contribution($op, $objectId, $objectName, $links),
  ];

  foreach ($hooks as $hook) {
    $hook->run();
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm
 */
function financeextras_civicrm_buildForm($formName, &$form) {
  $hooks = [
    new CRM_Financeextras_Hook_BuildForm__AdditionalPaymentButton($form, $formName),
  ];

  foreach ($hooks as $hook) {
    $hook->buildForm();
  }
}
