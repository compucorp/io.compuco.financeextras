<?php

namespace Civi\Financeextras\Hook\Links\ContributionRecur;

/**
 * Template
 * @package Civi\Financeextras\Hook\Links\ContributionRecur
 */
class Template {

  /**
   * @var array
   */
  private array $links;
  /**
   * @var int
   */
  private int $contributionID;

  /**
   * @param int $contributionID
   * @param array $links
   */
  public function __construct(int $contributionID, array &$links) {
    $this->links = &$links;
    $this->contributionID = $contributionID;
  }

  /**
   * Checks contribution has any of the given payment processors.
   *
   * @return bool
   *   Whether contribution has any of the given payment processors.
   *
   * @throws \Civi\API\Exception
   */
  private function contributionHasPaymentProcessor(array $processors): bool {
    try {
      $contribution = \Civi\Api4\ContributionRecur::get(FALSE)
        ->addWhere('id', '=', $this->contributionID)
        ->addWhere('payment_processor_id:name', 'IN', $processors)
        ->setLimit(1)
        ->execute()
        ->first();

      return !empty($contribution);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function remove(): void {

    if ($this->contributionHasPaymentProcessor(['Direct Debit', 'GoCardless'])) {
      foreach ($this->links as $key => $link) {
        if (!empty($link['name']) && $link['name'] === 'View Template') {
          unset($this->links[$key]);
          break;
        }
      }
    }
  }

}
