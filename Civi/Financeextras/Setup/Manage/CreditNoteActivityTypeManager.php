<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Adds credit note specific activity types.
 */
class CreditNoteActivityTypeManager extends AbstractManager {

  const EMAIL_INVOICE_ACTIVITY = 'financeextras_credit_note_email_activity';
  const DOWNLOAD_INVOICE_ACTIVITY = 'financeextras_credit_note_download_activity';

  /**
   * Ensures Credit note activity types exists.
   */
  public function create(): void {
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'activity_type',
      'name' => self::EMAIL_INVOICE_ACTIVITY,
      'label' => 'Emailed credit note',
      'is_reserved' => 1,
      'is_active' => TRUE,
    ]);

    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'activity_type',
      'name' => self::DOWNLOAD_INVOICE_ACTIVITY,
      'label' => 'Downloaded credit note',
      'is_reserved' => 1,
      'is_active' => TRUE,
    ]);
  }

  /**
   * Removes the entity.
   */
  public function remove(): void {
    \Civi\Api4\OptionValue::delete(FALSE)
      ->addWhere('name', 'IN', [self::EMAIL_INVOICE_ACTIVITY, self::DOWNLOAD_INVOICE_ACTIVITY])
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  protected function toggle($status): void {
    \Civi\Api4\OptionValue::update(FALSE)
      ->addWhere('name', 'IN', [self::EMAIL_INVOICE_ACTIVITY, self::DOWNLOAD_INVOICE_ACTIVITY])
      ->addWhere('option_group_id:name', '=', 'activity_type')
      ->addValue('is_active', $status)
      ->execute();
  }

}
