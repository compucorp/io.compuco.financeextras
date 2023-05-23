<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Manages the option group and values that stores credit note allocation types.
 */
class CreditNoteAllocationTypeManager extends AbstractManager {

  const NAME = 'financeextras_credit_note_allocation_type';

  /**
   * Ensures Credit note allocation type option group and default option values exists.
   *
   * The option values in the option group will store the available allocation types
   * for credit note.
   */
  public function create(): void {
    \CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => self::NAME,
      'title' => ts('Credit Note Allocation Type'),
      'is_reserved' => 1,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => self::NAME,
      'name' => 'invoice',
      'label' => 'Invoice',
      'is_default' => TRUE,
      'is_active' => TRUE,
      'is_reserved' => TRUE,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => self::NAME,
      'name' => 'manual_refund_payment',
      'label' => 'Refund payment (manual)',
      'is_active' => TRUE,
      'is_reserved' => TRUE,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => self::NAME,
      'name' => 'online_refund_payment',
      'label' => 'Refund payment (online)',
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
