<?php

namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Action\FinanceExtrasCurrency\FormatAction;

/**
 * Money formatting for the extension's forms.
 *
 * Named after the extension rather than "Currency": newer CiviCRM core (6.20.1,
 * not 6.4.1) ships its own Civi\Api4\Currency entity, which replaces this
 * extension's class of the same name and drops the format action.
 */
class FinanceExtrasCurrency extends \Civi\Api4\Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new BasicGetFieldsAction(static::getEntityName(), __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * Formats an amount in a currency.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return \Civi\Api4\Action\FinanceExtrasCurrency\FormatAction
   */
  public static function format($checkPermissions = TRUE) {
    return (new FormatAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
