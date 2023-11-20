<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

/**
 * The ExtensionUtil class provides small stubs for accessing resources of this
 * extension.
 */
class CRM_Financeextras_ExtensionUtil {
  const SHORT_NAME = 'financeextras';
  const LONG_NAME = 'io.compuco.financeextras';
  const CLASS_PREFIX = 'CRM_Financeextras';

  /**
   * Translate a string using the extension's domain.
   *
   * If the extension doesn't have a specific translation
   * for the string, fallback to the default translations.
   *
   * @param string $text
   *   Canonical message text (generally en_US).
   * @param array $params
   * @return string
   *   Translated text.
   * @see ts
   */
  public static function ts($text, $params = []) {
    if (!array_key_exists('domain', $params)) {
      $params['domain'] = [self::LONG_NAME, NULL];
    }
    return ts($text, $params);
  }

  /**
   * Get the URL of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo'.
   *   Ex: 'http://example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function url($file = NULL) {
    if ($file === NULL) {
      return rtrim(CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME), '/');
    }
    return CRM_Core_Resources::singleton()->getUrl(self::LONG_NAME, $file);
  }

  /**
   * Get the path of a resource file (in this extension).
   *
   * @param string|NULL $file
   *   Ex: NULL.
   *   Ex: 'css/foo.css'.
   * @return string
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo'.
   *   Ex: '/var/www/example.org/sites/default/ext/org.example.foo/css/foo.css'.
   */
  public static function path($file = NULL) {
    // return CRM_Core_Resources::singleton()->getPath(self::LONG_NAME, $file);
    return __DIR__ . ($file === NULL ? '' : (DIRECTORY_SEPARATOR . $file));
  }

  /**
   * Get the name of a class within this extension.
   *
   * @param string $suffix
   *   Ex: 'Page_HelloWorld' or 'Page\\HelloWorld'.
   * @return string
   *   Ex: 'CRM_Foo_Page_HelloWorld'.
   */
  public static function findClass($suffix) {
    return self::CLASS_PREFIX . '_' . str_replace('\\', '_', $suffix);
  }

}

use CRM_Financeextras_ExtensionUtil as E;

function _financeextras_civix_mixin_polyfill() {
  if (!class_exists('CRM_Extension_MixInfo')) {
    $polyfill = __DIR__ . '/mixin/polyfill.php';
    (require $polyfill)(E::LONG_NAME, E::SHORT_NAME, E::path());
  }
}

/**
 * (Delegated) Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function _financeextras_civix_civicrm_config(&$config = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $template = CRM_Core_Smarty::singleton();

  $extRoot = __DIR__ . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'templates';

  if (is_array($template->template_dir)) {
    array_unshift($template->template_dir, $extDir);
  }
  else {
    $template->template_dir = [$extDir, $template->template_dir];
  }

  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
  _financeextras_civix_mixin_polyfill();
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function _financeextras_civix_civicrm_install() {
  _financeextras_civix_civicrm_config();
  if ($upgrader = _financeextras_civix_upgrader()) {
    $upgrader->onInstall();
  }
  _financeextras_civix_mixin_polyfill();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function _financeextras_civix_civicrm_postInstall() {
  _financeextras_civix_civicrm_config();
  if ($upgrader = _financeextras_civix_upgrader()) {
    if (is_callable([$upgrader, 'onPostInstall'])) {
      $upgrader->onPostInstall();
    }
  }
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function _financeextras_civix_civicrm_uninstall() {
  _financeextras_civix_civicrm_config();
  if ($upgrader = _financeextras_civix_upgrader()) {
    $upgrader->onUninstall();
  }
}

/**
 * (Delegated) Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function _financeextras_civix_civicrm_enable() {
  _financeextras_civix_civicrm_config();
  if ($upgrader = _financeextras_civix_upgrader()) {
    if (is_callable([$upgrader, 'onEnable'])) {
      $upgrader->onEnable();
    }
  }
  _financeextras_civix_mixin_polyfill();
}

/**
 * (Delegated) Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 * @return mixed
 */
function _financeextras_civix_civicrm_disable() {
  _financeextras_civix_civicrm_config();
  if ($upgrader = _financeextras_civix_upgrader()) {
    if (is_callable([$upgrader, 'onDisable'])) {
      $upgrader->onDisable();
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *   for 'enqueue', returns void
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function _financeextras_civix_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  if ($upgrader = _financeextras_civix_upgrader()) {
    return $upgrader->onUpgrade($op, $queue);
  }
}

/**
 * @return CRM_Financeextras_Upgrader
 */
function _financeextras_civix_upgrader() {
  if (!file_exists(__DIR__ . '/CRM/Financeextras/Upgrader.php')) {
    return NULL;
  }
  else {
    return CRM_Financeextras_Upgrader_Base::instance();
  }
}

/**
 * Inserts a navigation menu item at a given place in the hierarchy.
 *
 * @param array $menu - menu hierarchy
 * @param string $path - path to parent of this item, e.g. 'my_extension/submenu'
 *    'Mailing', or 'Administer/System Settings'
 * @param array $item - the item to insert (parent/child attributes will be
 *    filled for you)
 *
 * @return bool
 */
function _financeextras_civix_insert_navigation_menu(&$menu, $path, $item) {
  // If we are done going down the path, insert menu
  if (empty($path)) {
    $menu[] = [
      'attributes' => array_merge([
        'label'      => CRM_Utils_Array::value('name', $item),
        'active'     => 1,
      ], $item),
    ];
    return TRUE;
  }
  else {
    // Find an recurse into the next level down
    $found = FALSE;
    $path = explode('/', $path);
    $first = array_shift($path);
    foreach ($menu as $key => &$entry) {
      if ($entry['attributes']['name'] == $first) {
        if (!isset($entry['child'])) {
          $entry['child'] = [];
        }
        $found = _financeextras_civix_insert_navigation_menu($entry['child'], implode('/', $path), $item);
      }
    }
    return $found;
  }
}

/**
 * (Delegated) Implements hook_civicrm_navigationMenu().
 */
function _financeextras_civix_navigationMenu(&$nodes) {
  if (!is_callable(['CRM_Core_BAO_Navigation', 'fixNavigationMenu'])) {
    _financeextras_civix_fixNavigationMenu($nodes);
  }
}

/**
 * Given a navigation menu, generate navIDs for any items which are
 * missing them.
 */
function _financeextras_civix_fixNavigationMenu(&$nodes) {
  $maxNavID = 1;
  array_walk_recursive($nodes, function($item, $key) use (&$maxNavID) {
    if ($key === 'navID') {
      $maxNavID = max($maxNavID, $item);
    }
  });
  _financeextras_civix_fixNavigationMenuItems($nodes, $maxNavID, NULL);
}

function _financeextras_civix_fixNavigationMenuItems(&$nodes, &$maxNavID, $parentID) {
  $origKeys = array_keys($nodes);
  foreach ($origKeys as $origKey) {
    if (!isset($nodes[$origKey]['attributes']['parentID']) && $parentID !== NULL) {
      $nodes[$origKey]['attributes']['parentID'] = $parentID;
    }
    // If no navID, then assign navID and fix key.
    if (!isset($nodes[$origKey]['attributes']['navID'])) {
      $newKey = ++$maxNavID;
      $nodes[$origKey]['attributes']['navID'] = $newKey;
      $nodes[$newKey] = $nodes[$origKey];
      unset($nodes[$origKey]);
      $origKey = $newKey;
    }
    if (isset($nodes[$origKey]['child']) && is_array($nodes[$origKey]['child'])) {
      _financeextras_civix_fixNavigationMenuItems($nodes[$origKey]['child'], $maxNavID, $nodes[$origKey]['attributes']['navID']);
    }
  }
}

/**
 * (Delegated) Implements hook_civicrm_entityTypes().
 *
 * Find any *.entityType.php files, merge their content, and return.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function _financeextras_civix_civicrm_entityTypes(&$entityTypes) {
  $entityTypes = array_merge($entityTypes, [
    'CRM_Financeextras_DAO_BatchOwnerOrganisation' => [
      'name' => 'BatchOwnerOrganisation',
      'class' => 'CRM_Financeextras_DAO_BatchOwnerOrganisation',
      'table' => 'financeextras_batch_owner_org',
    ],
    'CRM_Financeextras_DAO_Company' => [
      'name' => 'Company',
      'class' => 'CRM_Financeextras_DAO_Company',
      'table' => 'financeextras_company',
    ],
    'CRM_Financeextras_DAO_CreditNote' => [
      'name' => 'CreditNote',
      'class' => 'CRM_Financeextras_DAO_CreditNote',
      'table' => 'financeextras_credit_note',
    ],
    'CRM_Financeextras_DAO_CreditNoteAllocation' => [
      'name' => 'CreditNoteAllocation',
      'class' => 'CRM_Financeextras_DAO_CreditNoteAllocation',
      'table' => 'financeextras_credit_note_allocation',
    ],
    'CRM_Financeextras_DAO_CreditNoteLine' => [
      'name' => 'CreditNoteLine',
      'class' => 'CRM_Financeextras_DAO_CreditNoteLine',
      'table' => 'financeextras_credit_note_line',
    ],
    'CRM_Financeextras_DAO_ExchangeRate' => [
      'name' => 'ExchangeRate',
      'class' => 'CRM_Financeextras_DAO_ExchangeRate',
      'table' => 'financeextras_exchange_rate',
    ],
  ]);
}
