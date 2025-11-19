<?php
namespace Civi\Api4;

/**
 * PaymentAttempt entity - Tracks payment attempts across all payment processors.
 *
 * @searchable secondary
 * @since 1.0
 * @package Civi\Api4
 */
class PaymentAttempt extends Generic\DAOEntity {

  /**
   * @return string
   */
  protected static function getDaoName(): string {
    return 'CRM_Financeextras_DAO_PaymentAttempt';
  }

}
