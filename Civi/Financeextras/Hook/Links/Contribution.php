<?php

namespace Civi\Financeextras\Hook\Links;

use Civi\Financeextras\Hook\Links\Contribution\Refund;

/**
 * Contribution
 * @package Civi\Financeextras\Hook\Links;
 */
class Contribution {

  /**
   * @var array
   */
  private $links;

  /**
   * @var string
   */
  private $objectName;

  /**
   * @var string
   */
  private $objectId;

  /**
   * @var string
   */
  private $op;

  /**
   * Contribution constructor.
   *
   * @param string $op
   * @param string $objectId
   * @param string $objectName
   * @param array $links
   */
  public function __construct(string $op, string $objectId, string $objectName, array &$links) {
    $this->op = $op;
    $this->objectId = $objectId;
    $this->objectName = $objectName;
    $this->links = &$links;
  }

  public function run(): void {
    if (!$this->shouldRun()) {
      return;
    }

    $links = [
      new Refund((int) $this->objectId, $this->links),
    ];
    foreach ($links as $link) {
      $link->add();
    }
  }

  private function shouldRun(): bool {
    if ($this->objectName !== 'Contribution' && $this->op !== 'contribution.selector.row') {
      return FALSE;
    }

    return TRUE;
  }

}
