<?php

use Civi\Api4\CreditNote;
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Credit Note delete Form controller class.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Financeextras_Form_Contribute_CreditNoteDelete extends CRM_Core_Form {

  /**
   * Credit Note to delete.
   *
   * @var int
   */
  public $id;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    CRM_Utils_System::setTitle('Delete Credit Note');

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
      CreditNote::deleteWithItems()
        ->addWhere('id', '=', $this->id)
        ->execute();
      CRM_Core_Session::setStatus(E::ts('Credit Note is deleted successfully.'), ts('Credit Note deleted'), 'success');
    }
  }

}
