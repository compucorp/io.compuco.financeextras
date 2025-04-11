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
      $processedContributions[] = $affectedContribution;
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
    $transactionIds = explode(',', $contribution['trxn_id']);
    $lastTransactionId = $transactionIds[count($transactionIds) - 1] ?? '';

    $financialTrxns = $this->getFinancialTrxnsByContributionId((int) $contribution['id']);

    foreach ($financialTrxns as $financialTrxn) {
      $paymentProcessorId = $financialTrxn['payment_processor_id'] ?? $paymentProcessorId;
      if (number_format($financialTrxn['total_amount'], 2) === number_format($contribution['total_amount'], 2)) {
        $createFinancialTrxn = FALSE;
        break;
      }
    }

    if ($createFinancialTrxn) {
      $toFinancialAccountId = $this->getFinancialAccountByPaymentProcessorId((int) $paymentProcessorId);
      $paymentInstrumentId = $this->getPaymentInstrumentByPaymentProcessorId((int) $paymentProcessorId);

      civicrm_api3('FinancialTrxn', 'create', [
        'to_financial_account_id' => $toFinancialAccountId ?? 'Payment Processor Account',
        'trxn_date' => $contribution['receive_date'],
        'total_amount' => $contribution['total_amount'],
        'fee_amount' => $contribution['fee_amount'],
        'net_amount' => $contribution['net_amount'],
        'currency' => $contribution['currency'],
        'is_payment' => 1,
        'trxn_id' => $lastTransactionId,
        'status_id' => 'Completed',
        'payment_instrument_id' => $paymentInstrumentId ?? key(\CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1')),
        'payment_processor_id' => $paymentProcessorId > 0 ? $paymentProcessorId : NULL,
        'entity_id' => $contribution['id'],
        'contribution_id' => $contribution['id'],
      ]);
    }
  }

  private function createLineItem(array $contribution, array $membership): array {
    if (isset($membership['membership_id.membership_type_id'], $membership['membership_id'])) {
      $priceFieldValue = civicrm_api3('PriceFieldValue', 'get', [
        'sequential'         => 1,
        'membership_type_id' => $membership['membership_id.membership_type_id'],
        'options'            => ['limit' => 1, 'sort' => "id asc"],
      ])['values'][0] ?? [];
      if (empty($priceFieldValue)) {
        $priceFieldValue = $this->getContributionPriceFieldValue();
      }
      $entityId = $membership['membership_id'];
      $entityTable = 'civicrm_membership';
    }
    else {
      $priceFieldValue = $this->getContributionPriceFieldValue();
      $entityId = $contribution['id'];
      $entityTable = 'civicrm_contribution';
    }

    $lineItem = civicrm_api3('LineItem', 'create', [
      'sequential' => 1,
      'entity_table' => $entityTable,
      'entity_id' => $entityId,
      'contribution_id' => $contribution['id'],
      'price_field_id' => $priceFieldValue['price_field_id'] ?? NULL,
      'price_field_value_id' => $priceFieldValue['id'] ?? NULL,
      'label' => $priceFieldValue['label'] ?? '',
      'qty' => 1,
      'unit_price' => $contribution['total_amount'],
      'line_total' => $contribution['total_amount'],
      'financial_type_id' => $contribution['financial_type_id'] ?? 'Member Dues',
    ]);

    return $lineItem['values'] ?? [];
  }

  private function getFinancialTrxnsByContributionId(int $contributionId): array {
    $sql = "SELECT ft.*
    FROM civicrm_financial_trxn ft
    INNER JOIN civicrm_entity_financial_trxn eft ON eft.financial_trxn_id = ft.id
    WHERE eft.entity_table = 'civicrm_contribution' AND eft.entity_id = {$contributionId}
    ORDER BY ft.id ASC
    ";

    return \CRM_Core_DAO::executeQuery($sql)->fetchAll();
  }

  private function getFinancialAccountByPaymentProcessorId(int $paymentProcessorId) {
    $params = [
      'return' => ['financial_account_id'],
      'entity_table' => 'civicrm_payment_processor',
      'entity_id' => $paymentProcessorId,
      'options' => ['limit' => 1],
    ];

    $result = civicrm_api3('EntityFinancialAccount', 'get', $params);
    if ($result['count'] === 0) {
      return NULL;
    }

    return $result['values'][$result['id']]['financial_account_id'];
  }

  private function getPaymentInstrumentByPaymentProcessorId(int $paymentProcessorId) {
    $params = [
      'return' => ['payment_instrument_id'],
      'id' => $paymentProcessorId,
      'is_test' => 0,
      'options' => ['limit' => 1],
    ];

    $result = civicrm_api3('PaymentProcessor', 'get', $params);
    if ($result['count'] === 0) {
      return NULL;
    }

    return $result['values'][$result['id']]['payment_instrument_id'];
  }

  private function getContributionPriceFieldValue(): array {
    $priceFieldValue = [];
    $priceSetId      = \CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_contribution_amount', 'id', 'name');
    $priceSet        = current(\CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    $priceField      = NULL;
    if (empty($priceSet['fields'])) {
      return $priceFieldValue;
    }

    foreach ($priceSet['fields'] as $field) {
      if ($field['name'] == 'contribution_amount') {
        $priceField = $field;
        break;
      }
    }

    if (empty($priceField)) {
      return $priceFieldValue;
    }

    return !empty($priceField['options']) ? current($priceField['options']) : $priceFieldValue;
  }

}
