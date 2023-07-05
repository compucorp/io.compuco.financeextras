<?php

namespace Civi\Api4;

use Civi\Api4\Action\CreditNote\GetAction;
use Civi\Api4\Action\CreditNote\VoidAction;
use Civi\Api4\Action\CreditNote\ComputeTotalAction;
use Civi\Api4\Action\CreditNote\CreditNoteSaveAction;
use Civi\Api4\Action\CreditNote\DeleteWithItemsAction;

/**
 * CreditNote entity.
 *
 * Provided by the Finance Extras extension.
 *
 * @searchable primary
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

  /**
   * Deletes a credit note with line and transaction items.
   *
   * @param bool $checkPermissions
   *  Should permission be checked for the user.
   *
   * @return DAODeleteAction
   *   returns the credit note delete action
   */
  public static function deleteWithItems($checkPermissions = TRUE) {
    return (new DeleteWithItemsAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

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
   * Voids a credit note.
   *
   * @param bool $checkPermissions
   *  Should permission be checked for the user.
   *
   * @return \Civi\Api4\Action\CreditNote\VoidAction
   *   returns the credit note void action
   */
  public static function void($checkPermissions = TRUE) {
    return (new VoidAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * {@inheritDoc}
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'void' => ['access CiviContribute', 'edit contributions'],
      'computeTotal' => ['access CiviCRM', 'access CiviContribute'],
      'get' => ['access CiviCRM', 'access CiviContribute'],
      'default' => ['access CiviCRM', 'access CiviContribute', 'edit contributions'],
      'delete' => ['access CiviCRM', 'access CiviContribute', 'delete in CiviContribute'],
      'deleteWithItems' => ['access CiviCRM', 'access CiviContribute', 'delete in CiviContribute'],
    ];
  }

}
