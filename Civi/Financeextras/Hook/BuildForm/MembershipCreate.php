<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Financeextras_ExtensionUtil as E;

class MembershipCreate {

  /**
   * @param \CRM_Member_Form_Membership $form
   */
  public function __construct(private \CRM_Member_Form_Membership $form) {
  }

  public function handle() {
    $this->configureRecordContributionField();
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
  private function configureRecordContributionField() {
    if (!$this->isEdit()) {
      $this->form->addElement('radio', 'fe_member_type', NULL, ts('Paid Membership'), 'paid_member');
      $this->form->addElement('radio', 'fe_member_type', NULL, ts('Free Membership'), 'free_member');
      $this->form->add('checkbox', 'fe_record_payment_check', ts('Record Payment'), NULL);
      $this->form->add('text', 'fe_record_payment_amount', ts('Amount'), ['readonly' => TRUE]);

      $accountsReceivablePaymentMethodId = array_search('accounts_receivable', \CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'validate'));
      \Civi::resources()->addVars('financeextras', ['accounts_receivable_payment_method' => $accountsReceivablePaymentMethodId]);

      \Civi::resources()->add([
        'scriptFile' => [E::LONG_NAME, 'js/modifyMemberForm.js'],
        'region' => 'page-header',
      ]);

      \Civi::resources()->add([
        'template' => 'CRM/Financeextras/Form/Member/AddPayment.tpl',
        'region' => 'page-body',
      ]);
      \Civi::resources()->addVars('financeextras', ['currencySymbol' => $this->getCurrencySymbol()]);
    }
  }

  /**
   * @return string
   */
  private function getCurrencySymbol() {
    $config = \CRM_Core_Config::singleton();
    return \CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', $config->defaultCurrency, 'symbol', 'name');
  }

  private function isEdit() {
    return !empty($this->form->_id);
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
    return $formName === "CRM_Member_Form_Membership" && $form->_mode !== 'live' && ($form->getAction() & \CRM_Core_Action::ADD);
  }

}
