<?php


namespace Civi\Financeextras\Test\Fabricator;

class ContributionFabricator extends AbstractBaseFabricator {

  /**
   * Entity name.
   *
   * @var string
   */
  protected static $entityName = 'Contribution';

  /**
   * Fabricates a contribution with given parameters.
   *
   * @param array $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function fabricate(array $params = []) {
    $contribution = parent::fabricate($params);

    $contributionSoftParams = \CRM_Utils_Array::value('soft_credit', $params);
    if (!empty($contributionSoftParams)) {
      $contributionSoftParams['contribution_id'] = $contribution['id'];
      $contributionSoftParams['currency'] = $contribution['currency'];
      $contributionSoftParams['amount'] = $contribution['total_amount'];

      \CRM_Contribute_BAO_ContributionSoft::add($contributionSoftParams);
    }

    return $contribution;
  }

}
