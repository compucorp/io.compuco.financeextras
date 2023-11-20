<?php

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Exchange Rate Delete form controller
 */
class CRM_Financeextras_Form_ExchangeRate_Delete extends CRM_Core_Form {

  /**
   * Exchange rate value to delete
   * @var int
   */
  public $id;

  public function preProcess() {
    CRM_Utils_System::setTitle('Delete Exchange Rate Value');

    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $url = CRM_Utils_System::url('civicrm/exchange-rate', 'reset=1');
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }

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

  public function postProcess() {
    if (!empty($this->id)) {
      \Civi\Api4\ExchangeRate::delete(FALSE)
        ->addWhere('id', '=', $this->id)
        ->execute();
      CRM_Core_Session::setStatus(E::ts('Exchange Rate Value deleted successfully.'), ts('Item deleted'), 'success');
    }
  }

}
