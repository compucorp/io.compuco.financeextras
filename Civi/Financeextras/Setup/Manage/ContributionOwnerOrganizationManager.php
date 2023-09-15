<?php

namespace Civi\Financeextras\Setup\Manage;

/**
 * Managing 'Contribution Owner Organization' custom group and its fields.
 */
class ContributionOwnerOrganizationManager extends AbstractManager {

  public function create(): void {
    // nothing to do here, the custom group will be created automatically
    // because it is defined in the extension XML files.
  }

  public function remove(): void {
    $customFields = [
      'owner_organization',
    ];
    foreach ($customFields as $customFieldName) {
      civicrm_api3('CustomField', 'get', [
        'name' => $customFieldName,
        'custom_group_id' => 'financeextras_contribution_owner',
        'api.CustomField.delete' => ['id' => '$value.id'],
      ]);
    }

    civicrm_api3('CustomGroup', 'get', [
      'name' => 'financeextras_contribution_owner',
      'api.CustomGroup.delete' => ['id' => '$value.id'],
    ]);
  }

  public function toggle($status): void {
    civicrm_api3('CustomGroup', 'get', [
      'name' => 'financeextras_contribution_owner',
      'api.CustomGroup.create' => ['id' => '$value.id', 'is_active' => $status],
    ]);
  }

}
