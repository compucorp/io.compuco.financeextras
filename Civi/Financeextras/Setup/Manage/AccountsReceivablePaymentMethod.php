<?php

namespace Civi\Financeextras\Setup\Manage;

class AccountsReceivablePaymentMethod extends AbstractManager {

  public function create(): void {
    $accountsReceivableFinancialAccountId = civicrm_api3('FinancialAccount', 'getvalue', [
      'return' => 'id',
      'name' => 'Accounts Receivable',
    ]);

    if (empty($this->getAccountsReceivablePaymentMethodId())) {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => 'payment_instrument',
        'label' => 'Accounts Receivable',
        'name' => 'accounts_receivable',
        'financial_account_id' => $accountsReceivableFinancialAccountId,
        'is_reserved' => 1,
        'is_active' => 1,
      ]);
    }
  }

  public function remove(): void {
    $accountsReceivablePaymentMethodId = $this->getAccountsReceivablePaymentMethodId();
    if (!empty($accountsReceivablePaymentMethodId)) {
      civicrm_api3('OptionValue', 'delete', [
        'id' => $accountsReceivablePaymentMethodId,
      ]);
    }
  }

  protected function toggle($status): void {
    civicrm_api3('OptionValue', 'get', [
      'name' => "accounts_receivable",
      'api.OptionValue.create' => ['id' => '$value.id', 'is_active' => $status],
    ]);
  }

  private function getAccountsReceivablePaymentMethodId(): ?int {
    $paymentMethod = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'option_group_id' => 'payment_instrument',
      'name' => 'accounts_receivable',
    ]);

    if (!empty($paymentMethod['id'])) {
      return  $paymentMethod['id'];
    }

    return NULL;
  }

}
