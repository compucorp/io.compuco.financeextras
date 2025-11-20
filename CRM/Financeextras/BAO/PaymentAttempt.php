<?php
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Business Access Object for PaymentAttempt entity (generic across all processors)
 *
 * Tracks payment attempts for routing webhooks back to contributions.
 * Generic implementation supports Stripe, GoCardless, ITAS, and other processors.
 */
class CRM_Financeextras_BAO_PaymentAttempt extends CRM_Financeextras_DAO_PaymentAttempt {

  /**
   * Create a new PaymentAttempt based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Financeextras_DAO_PaymentAttempt|NULL
   */
  public static function create($params) {
    $className = 'CRM_Financeextras_DAO_PaymentAttempt';
    $entityName = 'PaymentAttempt';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Find a PaymentAttempt record by processor session ID
   *
   * @param string $sessionId Processor session ID (cs_... for Stripe, mandate_... for GoCardless)
   * @param string $processorType Processor type ('stripe', 'gocardless', etc.)
   * @return array|null Array of attempt data or NULL if not found
   */
  public static function findBySessionId($sessionId, $processorType = 'stripe') {
    if (empty($sessionId)) {
      return NULL;
    }

    $attempt = new self();
    $attempt->processor_session_id = $sessionId;
    $attempt->processor_type = $processorType;

    if ($attempt->find(TRUE)) {
      return $attempt->toArray();
    }

    return NULL;
  }

  /**
   * Find a PaymentAttempt record by processor payment ID
   *
   * @param string $paymentId Processor payment ID (pi_... for Stripe, payment_... for GoCardless)
   * @param string $processorType Processor type ('stripe', 'gocardless', etc.)
   * @return array|null Array of attempt data or NULL if not found
   */
  public static function findByPaymentId($paymentId, $processorType = 'stripe') {
    if (empty($paymentId)) {
      return NULL;
    }

    $attempt = new self();
    $attempt->processor_payment_id = $paymentId;
    $attempt->processor_type = $processorType;

    if ($attempt->find(TRUE)) {
      return $attempt->toArray();
    }

    return NULL;
  }

  /**
   * Find a PaymentAttempt record by Contribution ID
   *
   * @param int $contributionId CiviCRM Contribution ID
   * @return array|null Array of attempt data or NULL if not found
   */
  public static function findByContributionId($contributionId) {
    if (empty($contributionId)) {
      return NULL;
    }

    $attempt = new self();
    $attempt->contribution_id = $contributionId;

    if ($attempt->find(TRUE)) {
      return $attempt->toArray();
    }

    return NULL;
  }

  /**
   * Get available statuses for PaymentAttempt
   *
   * @return array Status options
   */
  public static function getStatuses() {
    return [
      'pending' => E::ts('Pending'),
      'completed' => E::ts('Completed'),
      'failed' => E::ts('Failed'),
      'cancelled' => E::ts('Cancelled'),
    ];
  }

}
