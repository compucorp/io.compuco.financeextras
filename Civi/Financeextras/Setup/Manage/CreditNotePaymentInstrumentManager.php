<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Adds credit note payment instrument method.
 */
class CreditNotePaymentInstrumentManager extends AbstractManager {

  /**
   * Ensures Credit note payment instrument exists.
   */
  public function create(): void {
    $paymentInstrument = [
      'option_group_id' => "payment_instrument",
      'label' => "Credit Note",
      'name' => "credit_note",
      'is_active' => TRUE,
      'is_reserved' => TRUE,
      'filter' => 1,
      'financial_account_id' => $this->getFinancialAccount(),
    ];
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists($paymentInstrument);
  }

  /**
   * Removes the entity.
   */
  public function remove(): void {
    \Civi\Api4\OptionValue::delete(FALSE)
      ->addWhere('name', '=', 'credit_note')
      ->addWhere('option_group_id:name', '=', 'payment_instrument')
      ->execute();
  }

  /**
   * {@inheritDoc}
   */
  protected function toggle($status): void {
    \Civi\Api4\OptionValue::update(FALSE)
      ->addWhere('name', '=', 'credit_note')
      ->addWhere('option_group_id:name', '=', 'payment_instrument')
      ->addValue('is_active', $status)
      ->execute();
  }

  protected function getFinancialAccount() {
    $financialAccounts = \Civi\Api4\FinancialAccount::get(FALSE)
      ->addSelect('id', 'is_default', 'name')
      ->addWhere('financial_account_type_id:name', '=', 'Asset')
      ->addOrderBy('id', 'ASC')
      ->execute();

    $account = $financialAccounts->first()['id'];
    foreach ($financialAccounts as $financialAccount) {
      // If there's a default financial account, use that instead.
      if ($financialAccount['is_default']) {
        $account = $financialAccount['id'];
      }
    }

    return $account;
  }

}
