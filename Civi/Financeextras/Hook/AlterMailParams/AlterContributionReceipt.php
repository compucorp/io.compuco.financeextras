<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

/**
 * Updates contribution receipt mail.
 */
class AlterContributionReceipt {

  /**
   * @param array $params
   *   Mail parameters.
   * @param string $context
   *   Mail context.
   */
  public function __construct(private array &$params, private string $context) {
  }

  /**
   * Updates contribution receipt mail.
   */
  public function handle() {
    if (empty($this->params['tplParams']['contribution']) && !empty($this->params['tokenContext']['contributionId'])) {
      $contribution = \Civi\Api4\Contribution::get()
        ->addWhere('id', '=', $this->params['tokenContext']['contributionId'])
        ->execute()
        ->first();

      if (!empty($contribution['tax_amount'])) {
        $this->params['tplParams']['totalTaxAmount'] = $contribution['tax_amount'];
      }

      $this->params['tplParams']['contribution'] = $contribution;
    }
  }

  /**
   * Determines if the hook will run.
   *
   * @param array $params
   *   Mail parameters.
   * @param string $context
   *   Mail context.
   *
   * @return bool
   *   returns TRUE if hook should run, FALSE otherwise.
   */
  public static function shouldHandle(array $params, $context) {
    $component = $params['valueName'] ?? '';
    if ($component !== 'contribution_offline_receipt' || $context !== 'messageTemplate') {
      return FALSE;
    }

    return TRUE;
  }

}
