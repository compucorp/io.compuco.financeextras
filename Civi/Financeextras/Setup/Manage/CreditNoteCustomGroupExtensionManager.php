<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Registers the CreditNote entity as custom-fieldable.
 *
 */
class CreditNoteCustomGroupExtensionManager extends AbstractManager {

  /**
   * Entity name as it appears in `cg_extend_objects.value`.
   */
  const VALUE = 'CreditNote';

  /**
   * Underlying table name. Stored in `cg_extend_objects.name`.
   */
  const TABLE_NAME = 'financeextras_credit_note';

  /**
   * {@inheritDoc}
   */
  public function create(): void {
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'cg_extend_objects',
      'value' => self::VALUE,
      'name' => self::TABLE_NAME,
      'label' => ts('Credit Notes'),
      'grouping' => NULL,
      'filter' => 0,
      'is_active' => TRUE,
      'is_reserved' => TRUE,
    ]);
  }

  /**
   * {@inheritDoc}
   */
  public function remove(): void {
    \Civi\Api4\OptionValue::delete(FALSE)
      ->addWhere('option_group_id:name', '=', 'cg_extend_objects')
      ->addWhere('value', '=', self::VALUE)
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  protected function toggle($status): void {
    \Civi\Api4\OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'cg_extend_objects')
      ->addWhere('value', '=', self::VALUE)
      ->addValue('is_active', $status)
      ->execute();
  }

}
