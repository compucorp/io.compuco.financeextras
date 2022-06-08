<?php

namespace Civi\Financeextras\Setup\Manage;

class AccountsReceivablePaymentMethod extends AbstractManager {

  public function create() {
    $accountsReceivableFinancialAccountId = civicrm_api3('FinancialAccount', 'getvalue', [
      'return' => 'id',
      'name' => "Accounts Receivable",
    ]);

    civicrm_api3('OptionValue', 'create', [
      'option_group_id' => 'payment_instrument',
      'label' => 'Accounts Receivable',
      'value' => 'accounts_receivable',
      'financial_account_id' => $accountsReceivableFinancialAccountId,
      'is_reserved' => 1,
      'is_active' => 1,
    ]);
  }

  public function remove() {
    civicrm_api3('OptionValue', 'get', [
      'name' => "accounts_receivable",
      'api.OptionValue.delete' => ['id' => '$value.id'],
    ]);
  }

  protected function toggle($status) {
    civicrm_api3('OptionValue', 'get', [
      'name' => "accounts_receivable",
      'api.OptionValue.create' => ['id' => '$value.id', 'is_active' => $status],
    ]);
  }
}
