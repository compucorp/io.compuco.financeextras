<?php

namespace Civi\Financeextras\Refund;

/**
 * PaymentProcessor
 * @package Civi\Financeextras\Payment
 */
class PaymentProcessor {

  /**
   * @var null|PaymentProcessor
   */
  private static $singleton = NULL;

  /**
   * @var array
   */
  private $cache = [];

  /**
   * @return \Civi\Financeextras\Refund\PaymentProcessor
   */
  public static function singleton(): PaymentProcessor {
    if (!self::$singleton) {
      self::$singleton = new self();
    }
    return self::$singleton;
  }

  /**
   * Gets refund processor IDs.
   *
   * @param bool $force
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function getRefundProcessorIDs(bool $force = TRUE): array {
    if (!isset($this->cache['processor_ids']) || $force) {
      $processors = civicrm_api3('PaymentProcessor', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'is_test' => 0,
      ])['values'];

      $refundProcessorIDs = [];
      foreach ($processors as $processor) {
        $processor = \Civi\Payment\System::singleton()->getById($processor['id']);
        if ($processor->supportsRefund()) {
          $refundProcessorIDs[] = $processor->getID();
        }
      }

      $this->cache['processor_ids'] = $refundProcessorIDs;
    }

    return $this->cache['processor_ids'];
  }

}
