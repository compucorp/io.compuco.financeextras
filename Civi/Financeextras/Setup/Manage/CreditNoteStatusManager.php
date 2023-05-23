<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Manages the option group and values that stores credit note statuses.
 */
class CreditNoteStatusManager extends AbstractManager {

  const NAME = 'financeextras_credit_note_status';

  /**
   * Ensures Credit note Status option group and default option values exists.
   *
   * The option values in the option group will store the available statuses
   * for credit note.
   */
  public function create(): void {
    \CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => self::NAME,
      'title' => ts('Credit Note Status'),
      'is_reserved' => 1,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => self::NAME,
      'name' => 'open',
      'label' => 'Open',
      'is_default' => TRUE,
      'is_active' => TRUE,
      'is_reserved' => TRUE,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => self::NAME,
      'name' => 'fully_allocated',
      'label' => 'Fully allocated',
      'is_active' => TRUE,
      'is_reserved' => TRUE,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => self::NAME,
      'name' => 'void',
      'label' => 'Void',
      'is_active' => TRUE,
      'is_reserved' => TRUE,
    ]);
  }

  /**
   * Removes the entity.
   */
  public function remove(): void {
    civicrm_api3('OptionGroup', 'get', [
      'return' => ['id'],
      'name' => self::NAME,
      'api.OptionGroup.delete' => ['id' => '$value.id'],
    ]);
  }

  /**
   * {@inheritDoc}
   */
  protected function toggle($status): void {
    civicrm_api3('OptionGroup', 'get', [
      'sequential' => 1,
      'name' => self::NAME,
      'api.OptionGroup.create' => ['id' => '$value.id', 'is_active' => $status],
    ]);
  }

}
