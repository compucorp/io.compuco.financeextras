<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Manages the Currency Exchange Rate custom field.
 */
class ExchangeRateFieldManager extends AbstractManager {

  const NAME = 'financeextras_currency_exchange_rates';

  /**
   * {@inheritDoc}
   */
  public function create(): void {
    // The custom group will be automatically created as
    // it's defined in the extension XML files
  }

  /**
   * Removes the entity.
   */
  public function remove(): void {
    // Prevent the linked currency_enabled option group
    // from being deleted with the field.
    \Civi\Api4\CustomField::update(FALSE)
      ->addValue('option_group_id', NULL)
      ->addWhere('name', '=', 'sales_tax_currency')
      ->execute();

    \Civi\Api4\CustomField::delete(FALSE)
      ->addWhere('custom_group_id:name', '=', self::NAME)
      ->execute();

    \Civi\Api4\CustomGroup::delete(FALSE)
      ->addWhere('name', '=', self::NAME)
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  protected function toggle($status): void {
    \Civi\Api4\CustomGroup::update(FALSE)
      ->addValue('is_active', $status)
      ->addWhere('name', '=', self::NAME)
      ->execute();
  }

}
