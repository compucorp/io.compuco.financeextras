<?php

namespace Civi\Financeextras\Hook\ValidateForm;

class OwnerOrganizationRetriever {

  /**
   * Gets a unique list of ids for the owner organizations of
   * the input financial types.
   *
   * @param array $financialTypes
   * @return array
   */
  public static function getFinancialTypesOwnerOrganizationIds($financialTypes) {
    $financialTypesCommaSeperated = implode(',', array_unique($financialTypes));
    $orgIds = [];

    $incomeAccountRelationId = key(\CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $query = "SELECT fa.contact_id FROM civicrm_entity_financial_account efa
              INNER JOIN civicrm_financial_account fa ON efa.financial_account_id = fa.id
              WHERE efa.entity_id IN ({$financialTypesCommaSeperated}) AND efa.entity_table = 'civicrm_financial_type' AND efa.account_relationship = {$incomeAccountRelationId}";
    $results = \CRM_Core_DAO::executeQuery($query);
    while ($results->fetch()) {
      $orgIds[] = $results->contact_id;
    }

    return array_unique($orgIds);
  }

}
