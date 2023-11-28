<?php

namespace Civi\Api4;

use Civi\Api4\Generic\DAOGetFieldsAction;
use Civi\Api4\Action\Currency\FormatAction;

class Currency extends \Civi\Api4\Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\DAOGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new DAOGetFieldsAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Creates or Updates a CreditNote with the line items.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return Civi\Api4\Action\CreditNote\CreditNoteSaveAction
   *   returns save order action
   */
  public static function format($checkPermissions = TRUE) {
    return (new FormatAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
