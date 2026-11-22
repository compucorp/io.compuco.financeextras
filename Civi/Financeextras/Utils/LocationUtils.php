<?php

namespace Civi\Financeextras\Utils;

/**
 * Reads a contact's location blocks for documents the system produces (invoices, credit notes).
 */
class LocationUtils {

  /**
   * Same shape as CRM_Core_BAO_Location::getValues(), with the email and phone blocks read
   * without a permission check.
   *
   * From CiviCRM 6.20 core reads those blocks through a permission-checked API, so a document
   * produced for someone who may not view the organisation (a member downloading their own
   * invoice, a scheduled job, a payment webhook) lost the organisation's email and phone.
   * Up to 6.4 core read them unchecked; an organisation's contact details printed on its own
   * invoice are not private to the viewer.
   *
   * @param int $contactId
   *
   * @return array
   */
  public static function getValues(int $contactId): array {
    $location = \CRM_Core_BAO_Location::getValues(['contact_id' => $contactId]) ?? [];
    $location['email'] = self::getBlocks('Email', $contactId);
    $location['phone'] = self::getBlocks('Phone', $contactId);

    return $location;
  }

  /**
   * A contact's blocks, primary first, indexed from 1 and escaped for HTML as core returns them.
   *
   * @param string $entity
   *   Email or Phone.
   * @param int $contactId
   *
   * @return array
   */
  private static function getBlocks(string $entity, int $contactId): array {
    $rows = (array) civicrm_api4($entity, 'get', [
      'checkPermissions' => FALSE,
      'where' => [['contact_id', '=', $contactId]],
      'orderBy' => ['is_primary' => 'DESC', 'id' => 'ASC'],
    ]);

    $blocks = [];
    foreach (array_values($rows) as $index => $row) {
      $blocks[$index + 1] = array_map(function ($value) {
        return is_string($value) ? htmlspecialchars($value) : $value;
      }, $row);
    }

    return $blocks;
  }

}
