<?php

namespace Civi\Financeextras\Test\Fabricator;

class PaymentProcessorFabricator extends AbstractBaseFabricator {

  /**
   * Entity's name.
   *
   * @var string
   */
  protected static $entityName = 'PaymentProcessor';

  /**
   * Array if default parameters to be used to create a payment processor.
   *
   * @var array
   */
  protected static $defaultParams = [];

  /**
   * Fabricates a payment processor with the given parameters.
   *
   * Handles financial_account_id by looking up the ID if a string name is provided.
   *
   * @param array $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   * @throws \Exception
   */
  public static function fabricate(array $params = []) {
    // If financial_account_id is a string, look up the ID
    if (!empty($params['financial_account_id']) && is_string($params['financial_account_id'])) {
      $financialAccount = civicrm_api3('FinancialAccount', 'get', [
        'name' => $params['financial_account_id'],
        'sequential' => 1,
      ]);

      if (!empty($financialAccount['values'][0]['id'])) {
        $params['financial_account_id'] = $financialAccount['values'][0]['id'];
      }
      else {
        // If not found, unset it to avoid validation error
        unset($params['financial_account_id']);
      }
    }

    return parent::fabricate($params);
  }

}
