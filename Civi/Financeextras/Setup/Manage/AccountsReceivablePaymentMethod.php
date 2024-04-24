<?php

namespace Civi\Financeextras\Setup\Manage;

class AccountsReceivablePaymentMethod extends AbstractManager {

  /**
   * The machine name of the payment method.
   */
  const NAME = 'accounts_receivable';

  /**
   * The title/label of the payment method.
   */
  const TITLE = 'Accounts Receivable';

  public function create(): void {
    $financialAccountId = $this->getAccountIdForAccountsReceivablePaymentMethod();
    if ($financialAccountId === 0) {
      throw new \Exception('Could not find a financial account to use with account receivable payment method.');
    }

    if (empty($this->getAccountsReceivablePaymentMethodId())) {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => 'payment_instrument',
        'label' => self::TITLE,
        'name' => self::NAME,
        'financial_account_id' => $financialAccountId,
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
      'name' => self::NAME,
      'api.OptionValue.create' => ['id' => '$value.id', 'is_active' => $status],
    ]);
  }

  private function getAccountsReceivablePaymentMethodId(): ?int {
    $paymentMethod = civicrm_api3('OptionValue', 'get', [
      'sequential' => 1,
      'return' => ['id'],
      'option_group_id' => 'payment_instrument',
      'name' => self::NAME,
    ]);

    if (!empty($paymentMethod['id'])) {
      return $paymentMethod['id'];
    }

    return NULL;
  }

  private function getAccountIdForAccountsReceivablePaymentMethod(): int {
    try {
      $financialAccountId = civicrm_api3('FinancialAccount', 'getvalue', [
        'return' => 'id',
        'name' => self::TITLE,
      ]);

      return $financialAccountId;
    }
    catch (\Throwable $e) {
      $financialAccountId = 0;

      $financialAccounts = \Civi\Api4\FinancialAccount::get(FALSE)
        ->addWhere('financial_account_type_id:name', '=', 'Asset')
        ->execute()
        ->getArrayCopy();

      if (empty($financialAccounts)) {
        return $financialAccountId;
      }

      foreach ($financialAccounts as $financialAccount) {
        if ((int) $financialAccount['is_default'] === 1) {
          $financialAccountId = $financialAccount['id'];
          break;
        }
      }

      return (int) ($financialAccountId > 0 ? $financialAccountId : $financialAccounts[0]['id']);
    }
  }

}
