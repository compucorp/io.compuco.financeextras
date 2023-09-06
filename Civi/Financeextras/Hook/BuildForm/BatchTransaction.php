<?php

namespace Civi\Financeextras\Hook\BuildForm;

class BatchTransaction {

  private $form;

  public function __construct($form) {
    $this->form = $form;
  }

  public function handle() {
    $this->setTransactionsOwnerOrganisationFilterValue();
  }

  /**
   * Sets the Owner Organisation filter value
   * for the transactions, based on the batch
   * owner organisations.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  private function setTransactionsOwnerOrganisationFilterValue() {
    $batchId = \CRM_Utils_Request::retrieve('bid', 'Int');
    if (empty($batchId)) {
      return;
    }

    $batchOwnerOrganisationIds = \CRM_Financeextras_BAO_BatchOwnerOrganisation::getByBatchId($batchId);
    if (empty($batchOwnerOrganisationIds)) {
      return;
    }

    $fieldName = 'custom_' . $this->getContributionOwnerOrganisationFieldId();
    $defaults[$fieldName] = $batchOwnerOrganisationIds;
    $this->form->setDefaults($defaults);
  }

  private function getContributionOwnerOrganisationFieldId() {
    return civicrm_api3('CustomField', 'getvalue', [
      'return' => 'id',
      'custom_group_id' => 'financeextras_contribution_owner',
      'name' => 'owner_organization',
    ]);
  }

  public static function shouldHandle($form, $formName) {
    return $formName === "CRM_Financial_Form_BatchTransaction";
  }

}
