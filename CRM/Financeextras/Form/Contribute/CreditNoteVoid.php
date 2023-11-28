<?php

use Civi\Api4\CreditNote;
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Credit Note void Form controller class.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Financeextras_Form_Contribute_CreditNoteVoid extends CRM_Core_Form {

  /**
   * Credit Note to void.
   *
   * @var int
   */
  public $id;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    CRM_Utils_System::setTitle('Void Credit Note');

    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Yes'),
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('No'),
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
        CreditNote::void()
          ->setId($this->id)
          ->execute();
        CRM_Core_Session::setStatus(E::ts('Credit Note voided successfully.'), ts('Credit Note voided'), 'success');
      }
      catch (\Throwable $th) {
        CRM_Core_Session::setStatus(E::ts($th->getMessage()), ts('Error voiding credit note'), 'error');
      }
    }
  }

}
