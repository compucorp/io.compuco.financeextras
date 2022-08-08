<?php

/**
 * Action get refundable balance.
 *
 * @param array $params
 *
 * @return array
 *   API result array.
 *
 * @throws \API_Exception
 * @throws \CiviCRM_API3_Exception
 * @throws \Civi\Payment\Exception\PaymentProcessorException
 */
function civicrm_api3_payment_processor_get_refunded_amount($params) {
  /** @var \CRM_Core_Payment $processor */
  $processor = Civi\Payment\System::singleton()->getById($params['payment_processor_id']);
  $processor->setPaymentProcessor(civicrm_api3('PaymentProcessor', 'getsingle', ['id' => $params['payment_processor_id']]));
  if (!$processor->supportsRefund()) {
    throw new API_Exception('Payment Processor does not support refund');
  }
  $result = $processor->getRefundedAmountByChargeId($params);
  return civicrm_api3_create_success([$result], $params);
}

/**
 * Action get refundable balance.
 *
 * @param array $params
 *
 */
function _civicrm_api3_payment_processor_get_refunded_amount_spec(&$params) {
  $params['payment_processor_id'] = [
    'api.required' => TRUE,
    'title' => ts('Payment processor'),
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['trxn_id'] = [
    'api.required' => TRUE,
    'title' => ts('Transaction id'),
    'type' => CRM_Utils_Type::T_STRING,
  ];
}
