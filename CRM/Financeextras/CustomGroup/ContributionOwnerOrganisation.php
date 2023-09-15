<?php

class CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation {

  /**
   * Sets the contribution owner organisation.
   *
   * @param int $contributionId
   * @param int $ownerOrganizationId
   * @return void
   */
  public static function setOwnerOrganisation($contributionId, $ownerOrganizationId) {
    $query = "INSERT INTO civicrm_value_financeextras_contribution_ownerorg (entity_id, owner_organization)
                    VALUES ({$contributionId}, {$ownerOrganizationId})
                    ON DUPLICATE KEY UPDATE owner_organization = {$ownerOrganizationId}";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Gets the company record associated
   * with the contribution owner organisation.
   *
   * @param int $contributionId
   * @return array
   */
  public static function getOwnerOrganisationCompany($contributionId) {
    $OwnerOrgQuery = "SELECT contact.organization_name as name, contact.image_URL as logo_url, company.* FROM civicrm_contribution cont
                      INNER JOIN civicrm_value_financeextras_contribution_ownerorg cont_ownerorg ON cont.id = cont_ownerorg.entity_id
                      INNER JOIN financeextras_company company ON cont_ownerorg.owner_organization = company.contact_id
                      INNER JOIN civicrm_contact contact ON company.contact_id = contact.id
                      WHERE cont.id = {$contributionId}
                      LIMIT 1";
    $contributionOwnerCompany = CRM_Core_DAO::executeQuery($OwnerOrgQuery);
    $contributionOwnerCompany->fetch();
    return $contributionOwnerCompany->toArray();
  }

}
