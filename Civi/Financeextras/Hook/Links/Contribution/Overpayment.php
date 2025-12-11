<?php

namespace Civi\Financeextras\Hook\Links\Contribution;

use Civi\Financeextras\Utils\OverpaymentUtils;

/**
 * Overpayment allocation link handler.
 *
 * @package Civi\Financeextras\Hook\Links\Contribution
 */
class Overpayment {

  /**
   * @var array
   */
  private $links;
  /**
   * @var int
   */
  private $contributionID;

  /**
   * Constructor.
   *
   * @param int $contributionID
   *   The contribution ID.
   * @param array $links
   *   The links array.
   */
  public function __construct($contributionID, &$links) {
    $this->links = &$links;
    $this->contributionID = $contributionID;
  }

  /**
   * Add the overpayment allocation link if eligible.
   *
   * @throws \CRM_Core_Exception
   */
  public function add(): void {
    if (!OverpaymentUtils::isEligibleForOverpaymentAllocation($this->contributionID)) {
      return;
    }

    if (!\CRM_Core_Permission::check('edit contributions')) {
      return;
    }

    $this->links[] = [
      'name' => 'Allocate overpayment to new credit note',
      'url' => 'civicrm/contribution/overpayment/allocate',
      'qs' => 'reset=1&contribution_id=' . $this->contributionID,
      'title' => 'Allocate overpayment to new credit note',
      'class' => 'medium-popup',
    ];
  }

}
