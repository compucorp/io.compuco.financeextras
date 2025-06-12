<?php

namespace Civi\Financeextras\Hook\Links\Contribution;

/**
 * CreditNote
 * @package Civi\Financeextras\Hook\Links\Contribution
 */
class CreditNote {

  /**
   * @var array
   */
  private $links;
  /**
   * @var int
   */
  private $contributionID;

  /**
   * @param $contributionID
   * @param $links
   */
  public function __construct($contributionID, &$links) {
    $this->links = &$links;
    $this->contributionID = $contributionID;
  }

  /**
   * Checks contribution has been cancelled.
   *
   * @return bool
   *   Array with recurring contribution's data.
   *
   * @throws \Civi\API\Exception
   */
  private function contributionHasStatus($statuses) {
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $this->contributionID)
      ->addWhere('contribution_status_id:name', 'IN', $statuses)
      ->setLimit(1)
      ->execute()
      ->first();

    return !empty($contribution);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function add(): void {

    if (!$this->contributionHasStatus(['Cancelled', 'Refunded', 'Failed', 'Chargeback']) && \CRM_Core_Permission::check('edit contributions')) {
      $this->links[] = [
        'name' => 'Add Credit Note',
        'url' => 'civicrm/contribution/creditnote',
        'qs' => 'reset=1&action=add&contribution_id=' . $this->contributionID,
        'title' => 'Add Credit Note',
        'class' => 'no-popup',
      ];
    }

    $havePayments = $this->getContributionPayments($this->contributionID);
    if (!$this->contributionHasStatus(['Failed', 'Completed', 'Cancelled']) && \CRM_Core_Permission::check('edit contributions') &&!$havePayments) {
      $this->links[] = [
        'name' => 'Void Contribution',
        'url' => 'civicrm/financeextras/contribution/void',
        'qs' => 'reset=1&action=void&id=' . $this->contributionID,
        'title' => 'Void Contribution',
        'class' => 'small-popup',
      ];
    }
  }

  /**
   * Retrieves a contribution payments using APIv4
   *
   * @return array
   *   Array of Contribution Payments
   */
  public function getContributionPayments(): array {
    return \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addJoin('FinancialTrxn AS financial_trxn', 'INNER', ['financial_trxn_id', '=', 'financial_trxn.id'], ['financial_trxn.is_payment', '=', 1])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('entity_id', '=', $this->contributionID)
      ->execute()
      ->getArrayCopy() ?? [];
  }

}
