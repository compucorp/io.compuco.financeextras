<?php

namespace Civi\Financeextras\Hook\Links\Contribution;

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Refund
 * @package Civi\Financeextras\Hook\Links\Contribution
 */
class Refund {

  /**
   * @var array
   */
  private array $links;
  /**
   * @var \Civi\Financeextras\Payment\Refund
   */
  private \Civi\Financeextras\Payment\Refund $paymentRefund;

  /**
   * @param $contributionID
   * @param $links
   */
  public function __construct($contributionID, &$links) {
    $this->links = &$links;
    $this->paymentRefund = new \Civi\Financeextras\Payment\Refund($contributionID);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function add(): void {
    if (!$this->paymentRefund->contactHasRefundPermission()) {
      return;
    }

    if (!$this->paymentRefund->isEligibleForRefund()) {
      return;
    }

    $this->links[] = [
      'name' => 'Submit Credit Card Refund',
      'url' => 'civicrm/financeextras/payment/refund/card',
      'qs' => 'reset=1&id=%%id%%',
      'title' => E::ts('Submit Credit Card Refund'),
    ];
  }

}
