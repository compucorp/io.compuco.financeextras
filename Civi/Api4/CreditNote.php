<?php

namespace Civi\Api4;

use Civi\Api4\Action\CreditNote\ComputeTotalAction;
use Civi\Api4\Action\CreditNote\CreditNoteSaveAction;

/**
 * CreditNote entity.
 *
 * Provided by the Finance Extras extension.
 *
 * @package Civi\Api4
 */
class CreditNote extends Generic\DAOEntity {

  /**
   * Creates or Updates a CreditNote with the line items.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return Civi\Api4\Action\CreditNote\CreditNoteSaveAction
   *   returns save order action
   */
  public static function save($checkPermissions = TRUE) {
    return (new CreditNoteSaveAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Compute the sum of the line items value.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return Civi\Api4\Action\CreditNote\ComputeTotalAction
   *   returns compute total action
   */
  public static function computeTotal($checkPermissions = FALSE) {
    return (new ComputeTotalAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
