<?php

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Implements Refund Button if supported Refund by Payment Processor
 */
class CRM_Financeextras_Hook_BuildForm__AdditionalPaymentButton {

  /**
   * @var string
   *   Path where template with javascript stored for adding new Refund Button.
   */
  private $templatePath;

  /**
   * @var int
   *   Contribution id for adding submit refund button.
   */
  private $contributionID;

  /**
   * @var \CRM_Contribute_Form_AdditionalPayment
   *   Form object that is being altered.
   */
  private $form;

  /**
   * @var string
   *   form name
   */
  private $formName;

  /**
   * CRM_Financeextras_Hook_BuildForm__AdditionalPaymentButton constructor.
   *
   * @param \CRM_Contribute_Form_AdditionalPayment $form
   * @param string $formName
   */
  public function __construct(&$form, $formName) {
    $this->form = $form;
    $this->formName = $formName;
    $this->templatePath = E::path() . '/templates/CRM/AdditionalPayment/Form/Payment';
  }

  public function buildForm(): void {
    if (!$this->validate()) {
      return;
    }

    $this->addRefundButton();
  }

  private function validate(): bool {
    if ($this->formName !== 'CRM_Contribute_Form_AdditionalPayment') {
      return FALSE;
    }

    $this->contributionID = $this->form->getVar('_id');

    $paymentRefund = new \Civi\Financeextras\Payment\Refund($this->contributionID);

    if (!$paymentRefund->contactHasRefundPermission()) {
      return FALSE;
    }

    if (!$paymentRefund->isEligibleForRefund()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Adds template to add the button for submit refund using form-bottom region
   */
  private function addRefundButton() {
    $formButtonValues = [
      "title" => ts('Submit Credit Card Refund'),
      "icon" => "fa-chevron-right",
      "link" => CRM_Utils_System::url("civicrm/financeextras/payment/refund/card?reset=1&id=" . $this->contributionID),
    ];
    $this->form->assign('contributionSubmitRefundButton', $formButtonValues);
    CRM_Core_Region::instance('form-bottom')->add([
      'template' => "{$this->templatePath}/RefundButton.tpl",
    ]);
  }

}
