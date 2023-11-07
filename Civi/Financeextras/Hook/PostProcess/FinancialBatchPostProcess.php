<?php

namespace Civi\Financeextras\Hook\PostProcess;

use CRM_Financeextras_BAO_BatchOwnerOrganisation as BatchOwnerOrganisation;

class FinancialBatchPostProcess {

  private $form;

  public function __construct($form) {
    $this->form = $form;
  }

  public function handle() {
    $this->updateBatchOwnerOrganisation();
  }

  private function updateBatchOwnerOrganisation() {
    $batchId = $this->form->_id;

    // In case of updating the owner organisations
    // in an existing batch, deleting all the existing
    // owner orgs is easier than trying to figure out which
    // should be kept and which should be removed.
    BatchOwnerOrganisation::deleteByBatchId($batchId);

    $submittedValues = $this->form->exportValues();
    if (empty($submittedValues['financeextras_owner_org_id'])) {
      return;
    }

    $ownerOrganisationIds = explode(',', $submittedValues['financeextras_owner_org_id']);
    foreach ($ownerOrganisationIds as $organisationId) {
      $params = [
        'batch_id' => $batchId,
        'owner_org_id' => $organisationId,
      ];
      BatchOwnerOrganisation::create($params);
    }
  }

  /**
   * Checks if the hook should run.
   *
   * @param CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName) {
    return $formName === "CRM_Financial_Form_FinancialBatch";
  }

}
