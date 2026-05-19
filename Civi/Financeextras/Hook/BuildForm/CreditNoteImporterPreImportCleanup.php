<?php

namespace Civi\Financeextras\Hook\BuildForm;

/**
 * Wipes the credit-note-importer external-id mapping table just before an import starts.
 */
class CreditNoteImporterPreImportCleanup {

  /**
   * Mappings older than this many days are eligible for cleanup
   */
  private const STALE_MAPPING_AGE_DAYS = 1;

  public function handle(): void {
    if (!\CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_value_credit_note_ext_id', 'created_at', FALSE)) {
      return;
    }

    \CRM_Core_DAO::executeQuery(
      'DELETE FROM civicrm_value_credit_note_ext_id WHERE created_at < (NOW() - INTERVAL %1 DAY)',
      [1 => [self::STALE_MAPPING_AGE_DAYS, 'Integer']]
    );
  }

  /**
   * Runs only when the user is on csvimport's Preview form and entity is CreditNoteImporter.
   *
   * @param \CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName): bool {
    if ($formName !== 'CRM_Csvimport_Import_Form_Preview') {
      return FALSE;
    }

    $entity = NULL;
    if (method_exists($form, 'getSubmittedValue')) {
      $entity = $form->getSubmittedValue('entity');
    }

    return $entity === 'CreditNoteImporter';
  }

}
