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
 * Implements hook_civicrm_postProcess().
 */
function financeextras_civicrm_postProcess($formName, $form) {
  $hooks = [
    \Civi\Financeextras\Hook\PostProcess\UpdateContributionExchangeRate::class,
  ];

  foreach ($hooks as $hook) {
    if ($hook::shouldHandle($form, $formName)) {
      (new $hook($form))->handle();
    }
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

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu/
 */
function financeextras_civicrm_navigationMenu(&$menu) {
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
