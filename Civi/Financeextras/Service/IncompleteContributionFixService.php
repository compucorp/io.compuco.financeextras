<?php

namespace Civi\Financeextras\Service;

class IncompleteContributionFixService {

  public function execute(): array {
    $contributionStatuses = array_flip(\CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate'));
    $affectedContributions = $this->fetchAffectedContributions($contributionStatuses);
    $processedContributions = [];

    while ($affectedContributions->fetch()) {
      $affectedContribution = $affectedContributions->toArray();
      $linkedMembership = $this->fetchLinkedMembership((int) $affectedContribution['id']);
      if (empty($linkedMembership)) {
        continue;
      }

      $processedContributions[] = $affectedContribution;
      $this->createFinancialTrxn($affectedContribution);
      $lineItem = $this->createLineItem($affectedContribution, $linkedMembership);

      if ($affectedContribution['contribution_status_id'] == $contributionStatuses['Pending']) {
        $updateStatusQuery = "
        UPDATE civicrm_contribution SET contribution_status_id  = {$contributionStatuses['Completed']}
        WHERE id = {$affectedContribution['id']}";
        \CRM_Core_DAO::executeQuery($updateStatusQuery);
        $affectedContribution['contribution_status_id'] = $contributionStatuses['Completed'];
      }

      \CRM_Financial_BAO_FinancialItem::add((object) $lineItem[0], (object) $affectedContribution);
    }

    return $processedContributions;
  }

  private function fetchAffectedContributions(array $contributionStatuses): object {
    $affectedContributionsQuery = "
    SELECT cc.* from civicrm_contribution cc
    LEFT JOIN civicrm_line_item li ON cc.id = li.contribution_id
    WHERE li.id IS NULL AND cc.contribution_status_id IN ({$contributionStatuses['Pending']}, {$contributionStatuses['Completed']})
    AND cc.is_pay_later = 0";

    $affectedContributions = \CRM_Core_DAO::executeQuery($affectedContributionsQuery);

    return $affectedContributions;
  }

  private function fetchLinkedMembership(int $contributionId): array {
    return civicrm_api3('MembershipPayment', 'get', [
      'sequential' => 1,
      'return' => ['membership_id', 'membership_id.membership_type_id'],
      'contribution_id' => $contributionId,
      'options' => ['limit' => 1],
    ])['values'][0] ?? [];
  }

  private function createFinancialTrxn(array $contribution): void {
    $createFinancialTrxn = TRUE;
    try {
      $paymentProcessorId = (int) civicrm_api3('PaymentProcessor', 'getvalue', [
        'is_test' => 0,
        'options' => ['limit' => 1],
        'name'    => 'Stripe',
        'return'  => 'id',
      ]);
    }
    catch (\Throwable $e) {
      $paymentProcessorId = 0;
    }
    $paymentInstrumentId = 'Credit Card';
    $transactionIds = explode(',', $contribution['trxn_id']);
    $lastTransactionId = $transactionIds[count($transactionIds) - 1];

    $financialTrxns = \Civi\Api4\FinancialTrxn::get(FALSE)
      ->addWhere('trxn_id', '=', $contribution['trxn_id'])
      ->execute()
      ->getArrayCopy();

    foreach ($financialTrxns as $financialTrxn) {
      $paymentProcessorId = $financialTrxn['payment_processor_id'];
      $paymentInstrumentId = $financialTrxn['payment_instrument_id'];
      if (number_format($financialTrxn['total_amount'], 2) === number_format($contribution['total_amount'], 2)) {
        $createFinancialTrxn = FALSE;
        break;
      }
    }

    if ($createFinancialTrxn) {
      civicrm_api3('FinancialTrxn', 'create', [
        'to_financial_account_id' => 'Payment Processor Account',
        'trxn_date' => $contribution['receive_date'],
        'total_amount' => $contribution['total_amount'],
        'fee_amount' => $contribution['fee_amount'],
        'net_amount' => $contribution['net_amount'],
        'currency' => $contribution['currency'],
        'is_payment' => 1,
        'trxn_id' => $lastTransactionId,
        'status_id' => 'Completed',
        'payment_instrument_id' => $paymentInstrumentId,
        'payment_processor_id' => $paymentProcessorId > 0 ? $paymentProcessorId : NULL,
        'entity_id' => $contribution['id'],
        'contribution_id' => $contribution['id'],
      ]);
    }
  }

  private function createLineItem(array $contribution, array $membership): array {
    $priceFieldValue = civicrm_api3('PriceFieldValue', 'get', [
      'sequential' => 1,
      'membership_type_id' => $membership['membership_id.membership_type_id'],
      'options' => ['limit' => 1, 'sort' => "id asc"],
    ])['values'][0] ?? [];

    $lineItem = civicrm_api3('LineItem', 'create', [
      'sequential' => 1,
      'entity_table' => 'civicrm_membership',
      'entity_id' => $membership['membership_id'],
      'contribution_id' => $contribution['id'],
      'price_field_id' => $priceFieldValue['price_field_id'],
      'price_field_value_id' => $priceFieldValue['id'],
      'label' => $priceFieldValue['name'],
      'qty' => 1,
      'unit_price' => $contribution['total_amount'],
      'line_total' => $contribution['total_amount'],
      'financial_type_id' => $contribution['financial_type_id'] ?? 'Member Dues',
    ]);

    return $lineItem['values'] ?? [];
  }

}
