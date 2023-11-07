<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Implements Refund Button if supported Refund by Payment Processor
 */
class AdditionalPaymentButton {

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
   * CRM_Financeextras_Hook_BuildForm__AdditionalPaymentButton constructor.
   *
   * @param \CRM_Contribute_Form_AdditionalPayment $form
   */
  public function __construct(&$form) {
    $this->form = $form;
    $this->templatePath = E::path() . '/templates/CRM/AdditionalPayment/Form/Payment';
  }

  public function handle(): void {
    $this->contributionID = $this->form->getVar('_id');

    $this->addRefundButton();
  }

  /**
   * Adds template to add the button for submit refund using form-bottom region
   */
  private function addRefundButton() {
    $formButtonValues = [
      "title" => ts('Submit Credit Card Refund'),
      "icon" => "fa-chevron-right",
      "link" => \CRM_Utils_System::url("civicrm/financeextras/payment/refund/card?reset=1&id=" . $this->contributionID),
    ];
    $this->form->assign('contributionSubmitRefundButton', $formButtonValues);
    \CRM_Core_Region::instance('form-bottom')->add([
      'template' => "{$this->templatePath}/RefundButton.tpl",
    ]);
  }

  public static function shouldHandle($form, $formName) {
    if ($formName !== 'CRM_Contribute_Form_AdditionalPayment') {
      return FALSE;
    }

    $contributionID = $form->getVar('_id');

    $paymentRefund = new \Civi\Financeextras\Payment\Refund($contributionID);

    if (!$paymentRefund->contactHasRefundPermission()) {
      return FALSE;
    }

    if (!$paymentRefund->isEligibleForRefund()) {
      return FALSE;
    }

    return TRUE;
  }

}
