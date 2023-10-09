<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Financeextras_ExtensionUtil as E;
use Civi\Financeextras\Utils\OptionValueUtils;
use CRM_Core_Action;

class ContributionCreate {

  /**
   * @param \CRM_Contribute_Form_Contribution $form
   */
  public function __construct(private \CRM_Contribute_Form_Contribution $form) {
  }

  public function handle() {
    $this->addCustomLineItemTemplate();
    $this->addCreditNoteCancelAction();
    $this->configureRecordPaymentField();
    $this->preventUserFromSettingContributionStatus();
  }

  /**
   * We are changing the CiviCRM core behavior that allow users
   * to set contibution status manually during create/edit, instead
   * this status will be set based on the payment allocated to the
   * contribution.
   */
  private function preventUserFromSettingContributionStatus() {
    try {
      $statusElement = $this->form->getElement('contribution_status_id');
      if (!$this->isEdit()) {
        // By default new contribution will have a pending status
        // and will be updated to the right status post payment(if any)
        $statusElement->setValue(OptionValueUtils::getValueForOptionValue('contribution_status', 'Pending'));
      }
      $statusElement->freeze();
    }
    catch (\Throwable $th) {
    }
  }

  /**
   * CiviCRM by default always shows the payment fields for new contribution,
   * This method configures the display of these payment related fields by
   * grouping them together as a block and adds a 'Record Payment' checkbox
   * that determines visibility of this block.
   *
   * Also, adds a payment amount field, that allows users to specify the amount
   * they would like to record for the contribution.
   */
  private function configureRecordPaymentField() {
    if (!$this->isEdit()) {
      $this->form->add('checkbox', 'fe_record_payment_check', ts('Record Payment'), NULL);
      $this->form->add('text', 'fe_record_payment_amount', ts('Amount'), NULL);
      \Civi::resources()->add([
        'scriptFile' => [E::LONG_NAME, 'js/modifyContributionForm.js'],
        'region' => 'page-header',
      ]);
      \Civi::resources()->add([
        'template' => 'CRM/Financeextras/Form/Contribute/AddPayment.tpl',
        'region' => 'page-body',
      ]);
      \Civi::resources()->addVars('financeextras', ['currencies' => \CRM_Core_OptionGroup::values('currencies_enabled')]);
    }
  }

  private function addCreditNoteCancelAction() {
    if (!$this->form->_id) {
      return;
    }

    \Civi::resources()->add([
      'scriptFile' => [E::LONG_NAME, 'js/addContributionCreditNoteBtn.js'],
      'region' => 'page-header',
    ]);

    $url = \CRM_Utils_System::url('civicrm/contribution/creditnote', 'reset=1&action=add&contribution_id=' . $this->form->_id);
    \Civi::resources()->addVars('financeextras', ['creditnote_btn_url' => $url]);
  }

  private function isEdit() {
    return !empty($this->form->_id);
  }

  /**
   * Defaults to opening a contribution with the line item editor view.
   */
  private function addCustomLineItemTemplate() {
    $lineItemEditorIsInstalled = 'installed' ===
    \CRM_Extension_System::singleton()->getManager()->getStatus('biz.jmaconsulting.lineitemedit');

    if (!$lineItemEditorIsInstalled) {
      return;
    }

    if (!in_array($this->form->_action, [CRM_Core_Action::ADD, CRM_Core_Action::UPDATE])) {
      return;
    }

    \Civi::resources()->add([
      'template' => 'CRM/Financeextras/Form/Contribute/CustomLineItem.tpl',
      'region' => 'page-body',
    ]);
  }

  /**
   * Checks if the hook should run.
   *
   * @param \CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName) {
    $addOrUpdate = ($form->getAction() & CRM_Core_Action::ADD) || ($form->getAction() & CRM_Core_Action::UPDATE);
    return $formName === "CRM_Contribute_Form_Contribution" &&  $addOrUpdate;
  }

}
