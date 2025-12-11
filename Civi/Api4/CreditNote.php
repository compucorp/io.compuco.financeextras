<?php

namespace Civi\Api4;

use Civi\Api4\Action\CreditNote\GetAction;
use Civi\Api4\Action\CreditNote\VoidAction;
use Civi\Api4\Action\CreditNote\RefundAction;
use Civi\Api4\Action\CreditNote\ComputeTotalAction;
use Civi\Api4\Action\CreditNote\CreditNoteSaveAction;
use Civi\Api4\Action\CreditNote\DeleteWithItemsAction;
use Civi\Api4\Action\CreditNote\AllocateOverpaymentAction;

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
   * Record a credit note refund.
   *
   * @param bool $checkPermissions
   *  Should permission be checked for the user.
   *
   * @return \Civi\Api4\Action\CreditNote\RefundAction
   *   returns the credit note refund action
   */
  public static function refund($checkPermissions = TRUE) {
    return (new RefundAction(__CLASS__, __FUNCTION__))
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
   * Allocate an overpayment to a new credit note.
   *
   * Creates a credit note for the overpayment amount and allocates it
   * to the contribution to balance the contribution. The remaining credit
   * can then be allocated to future invoices.
   *
   * @param bool $checkPermissions
   *   Should permission be checked for the user.
   *
   * @return \Civi\Api4\Action\CreditNote\AllocateOverpaymentAction
   *   Returns the allocate overpayment action.
   */
  public static function allocateOverpayment($checkPermissions = TRUE) {
    return (new AllocateOverpaymentAction(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * {@inheritDoc}
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'get' => ['access CiviCRM', 'access CiviContribute'],
      'void' => ['access CiviContribute', 'edit contributions'],
      'refund' => ['access CiviContribute', 'edit contributions'],
      'allocateOverpayment' => ['access CiviContribute', 'edit contributions'],
      'computeTotal' => ['access CiviCRM', 'access CiviContribute'],
      'default' => ['access CiviCRM', 'access CiviContribute', 'edit contributions'],
      'delete' => ['access CiviCRM', 'access CiviContribute', 'delete in CiviContribute'],
      'deleteWithItems' => ['access CiviCRM', 'access CiviContribute', 'delete in CiviContribute'],
    ];
  }

}
