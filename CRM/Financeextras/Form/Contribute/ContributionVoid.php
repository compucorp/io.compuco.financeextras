<?php

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Contribution void Form controller class.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Financeextras_Form_Contribute_ContributionVoid extends CRM_Core_Form {

  /**
   * Contribution to void.
   *
   * @var int
   */
  public $id;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    CRM_Utils_System::setTitle('Void Contribution');

    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $contributionResult = \Civi\Api4\Contribution::get(TRUE)
      ->addSelect('contribution_status_id:name')
      ->addWhere('id', '=', $this->id)
      ->execute()
      ->first();

    $contributionStatus = $contributionResult !== NULL ? $contributionResult['contribution_status_id:name'] : "";
    $popupMessage = "Are you sure you want to void this contribution? Invoices cannot be downloaded for void contributions.";
    if ($contributionStatus == "Completed") {
      $popupMessage = "Are you sure you want to void this contribution? Invoices cannot be downloaded for void contributions. To credit this invoice, please create a credit note and apply the credit to this contribution instead.";
    }

    $this->assign("popupMessage", $popupMessage);
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Proceed'),
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
        'isDefault' => TRUE,
      ],
    ]);

    parent::buildQuickForm();
  }

  /**
   * {@inheritDoc}
   */
  public function postProcess() {
    if (!empty($this->id)) {
      try {
        \Civi\Api4\Contribution::update(FALSE)
          ->addValue('id', $this->id)
          ->addValue('contribution_status_id:label', 'Cancelled')
          ->execute();
        CRM_Core_Session::setStatus(E::ts('Contribution voided successfully.'), ts('Contribution Voided'), 'success');
      }
      catch (\Throwable $th) {
        CRM_Core_Session::setStatus(E::ts($th->getMessage()), ts('Error voiding contribution'), 'error');
      }
    }
  }

}
