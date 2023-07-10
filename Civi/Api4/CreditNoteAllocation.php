<?php

namespace Civi\Api4;

use Civi\Api4\Action\CreditNoteAllocation\AllocateAction;

/**
 * CreditNoteAllocation entity.
 *
 * Provided by the Finance Extras extension.
 *
 * @package Civi\Api4
 */
class CreditNoteAllocation extends Generic\DAOEntity {

  /**
   * Allocate credit to contributions.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return Civi\Api4\Action\CreditNoteAllocation\Allocate
   *   returns compute total action
   */
  public static function allocate($checkPermissions = TRUE) {
    return (new AllocateAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
