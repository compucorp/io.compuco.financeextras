<?php

/**
 * Alters action links for contributions.
 */
class CRM_Financeextras_Hook_Links_Contribution {

  /**
   * ID for the current contribution.
   *
   * @var int
   */
  private $contributionId;

  /**
   * List of links for the current contribution.
   *
   * @var array
   */
  private $links;

  /**
   * CRM_Financeextras_Hook_Links_Contribution constructor.
   *
   * @param int $contributionId
   *  ID for the current contribution
   * @param array $links
   *  List of links for the current contribution
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function __construct($contributionId, &$links) {
    $this->contributionId = $contributionId;
    $this->links = &$links;
  }

  /**
   * Checks if the hook should run.
   *
   * @param string $op
   *  Link context
   * @param string $objectName
   *  Link entity
   *
   * @return bool
   */
  public static function shouldHandle($op, $objectName) {
    return $op == 'contribution.selector.row' &&
      $objectName == 'Contribution' &&
      CRM_Core_Permission::check('edit contributions');
  }

  /**
   * Checks contribution has been cancelled.
   *
   * @return bool
   *   Array with recurring contribution's data.
   *
   * @throws \Civi\API\Exception
   */
  private function isContributionCancelled() {
    $contribution = \Civi\Api4\Contribution::get()
      ->addWhere('id', '=', $this->contributionId)
      ->addWhere('contribution_status_id:name', '=', 'Cancelled')
      ->setLimit(1)
      ->execute()
      ->first();

    return !empty($contribution);
  }

  /**
   * Adds credit note action to contribution links.
   */
  public function alterLinks() {
    if (!$this->isContributionCancelled()) {
      $this->links[] = [
        'name' => 'Add Credit Note',
        'url' => 'civicrm/contribution/creditnote',
        'qs' => 'reset=1&action=add&contribution_id=' . $this->contributionId,
        'title' => 'Add Credit Note',
      ];
    }
  }

}
