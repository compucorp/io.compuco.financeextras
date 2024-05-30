<?php

namespace Civi\Financeextras\Hook\Links;

use Civi\Financeextras\Hook\Links\ContributionRecur\Template;

/**
 * ContributionRecur
 * @package Civi\Financeextras\Hook\Links;
 */
class ContributionRecur {

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
    $this->removeLinks();
  }

  private function removeLinks(): void {
    $links = [
      new Template((int) $this->objectId, $this->links),
    ];
    foreach ($links as $link) {
      $link->remove();
    }
  }

  public static function shouldHandle(string $op, ?string $objectName): bool {
    return $op === 'contribution.selector.recurring' && $objectName === 'Contribution';
  }

}
