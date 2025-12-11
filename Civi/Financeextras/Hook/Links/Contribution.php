<?php

namespace Civi\Financeextras\Hook\Links;

use Civi\Financeextras\Hook\Links\Contribution\Refund;
use Civi\Financeextras\Hook\Links\Contribution\CreditNote;
use Civi\Financeextras\Hook\Links\Contribution\Overpayment;

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

  public function handle(): void {
    $this->addLinks();
  }

  private function addLinks() {
    $links = [
      new Refund((int) $this->objectId, $this->links),
      new CreditNote((int) $this->objectId, $this->links),
      new Overpayment((int) $this->objectId, $this->links),
    ];
    foreach ($links as $link) {
      $link->add();
    }
  }

  public static function shouldHandle($op, $objectName): bool {
    return $op == 'contribution.selector.row' && $objectName == 'Contribution';
  }

}
