<?php

namespace Civi\Financeextras\Hook\Links;

use Civi\Financeextras\Payment\RefundTrait;
use CRM_Financeextras_ExtensionUtil as E;

/**
 * ContributionLinks
 * @package Civi\Financeextras\Hook\Links;
 */
class ContributionLinks {

  use RefundTrait;

  /**
   * @var array
   */
  private array $links;

  /**
   * @var string
   */
  private string $objectName;

  /**
   * @var int
   */
  private int $objectId;

  /**
   * @var string
   */
  private string $op;

  /**
   * ContributionLinks constructor.
   *
   * @param string $op
   * @param int $objectId
   * @param string $objectName
   * @param array $links
   */
  public function __construct(string $op, int $objectId, string $objectName, array &$links) {
    $this->op = $op;
    $this->objectId = $objectId;
    $this->objectName = $objectName;
    $this->links = &$links;
  }

  public function run(): void {
    if (!$this->shouldRun()) {
      return;
    }

    $this->links[] = [
      'name' => 'Submit Credit Card Refund',
      'url' => 'civicrm/financeextras/payment/refund/card',
      'qs' => 'reset=1&id=%%id%%',
      'title' => E::ts('Submit Credit Card Refund'),
    ];

  }

  /**
   * @return bool
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  private function shouldRun(): bool {
    if ($this->objectName !== 'Contribution' && $this->op !== 'contribution.selector') {
      return FALSE;
    }

    if (!$this->hasRefundPermission()) {
      return FALSE;
    }

    return $this->isContributionEligibleToRefund($this->objectId);
  }

}
