<?php

class CRM_Financeextras_Form_Company_Delete extends CRM_Core_Form {

  /**
   * Company to delete id
   *
   * @var int
   */
  private $id;

  public function preProcess() {
    CRM_Utils_System::setTitle(ts('Delete Company'));

    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $url = CRM_Utils_System::url('civicrm/admin/financeextras/company', 'reset=1');
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }

  public function buildQuickForm() {
    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Delete'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  public function postProcess() {
    if (!empty($this->id)) {
      CRM_Financeextras_BAO_Company::deleteById($this->id);

      CRM_Core_Session::setStatus(ts('Selected "Company" has been deleted.'), ts('Record Deleted'), 'success');
      $returnURL = CRM_Utils_System::url('civicrm/admin/financeextras/company', 'reset=1');
      CRM_Utils_System::redirect($returnURL);
    }
  }

}
