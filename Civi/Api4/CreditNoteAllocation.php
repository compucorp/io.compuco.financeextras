<?php

namespace Civi\Api4;

use Civi\Api4\Action\CreditNoteAllocation\GetAction;
use Civi\Api4\Action\CreditNoteAllocation\AllocateAction;
use Civi\Api4\Action\CreditNoteAllocation\ReverseAction;

/**
 * CreditNoteAllocation entity.
 *
 * Provided by the Finance Extras extension.
 *
 * @package Civi\Api4
 */
class CreditNoteAllocation extends Generic\DAOEntity {

  /**
   * {@inheritDoc}
   *
   * @param bool $checkPermissions
   * @return DAOGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new GetAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Allocate credit to contributions.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return Civi\Api4\Action\CreditNoteAllocation\Allocate
   *   returns credit note allocate action
   */
  public static function allocate($checkPermissions = TRUE) {
    return (new AllocateAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * Reverses credit allocation to contribution.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return Civi\Api4\Action\CreditNoteAllocation\Reverse
   *   returns credit note reverse action
   */
  public static function reverse($checkPermissions = TRUE) {
    return (new ReverseAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * {@inheritDoc}
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'get' => ['access CiviCRM', 'access CiviContribute'],
      'default' => ['access CiviCRM', 'access CiviContribute', 'edit contributions'],
    ];
  }

}
