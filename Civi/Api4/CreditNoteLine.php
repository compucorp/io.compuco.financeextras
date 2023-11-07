<?php

namespace Civi\Api4;

/**
 * CreditNoteLine entity.
 *
 * Provided by the Finance Extras extension.
 *
 * @package Civi\Api4
 */
class CreditNoteLine extends Generic\DAOEntity {

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
