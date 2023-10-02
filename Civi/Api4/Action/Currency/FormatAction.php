<?php

namespace Civi\Api4\Action\Currency;

use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;

/**
 * Format a monetary string..
 */
class FormatAction extends AbstractAction {
  use DAOActionTrait;

  /**
   * Currency to format to.
   *
   * @var string
   */
  protected $currency;

  /**
   * Value to be formatted.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Symbol should be included in formatted string.
   *
   * @var bool
   */
  protected $onlyNumber = FALSE;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    $result[] = \CRM_Utils_Money::format($this->value, $this->currency, $this->onlyNumber);
  }

}
