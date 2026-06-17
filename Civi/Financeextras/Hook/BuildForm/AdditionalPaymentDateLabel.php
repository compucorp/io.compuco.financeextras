<?php

namespace Civi\Financeextras\Hook\BuildForm;

/**
 * Renames the transaction date field label to 'Date' on the additional payment form.
 */
class AdditionalPaymentDateLabel {

  /**
   * @var \CRM_Contribute_Form_AdditionalPayment
   *   Form object that is being altered.
   */
  private \CRM_Contribute_Form_AdditionalPayment $form;

  /**
   * AdditionalPaymentDateLabel constructor.
   *
   * @param \CRM_Contribute_Form_AdditionalPayment $form
   */
  public function __construct(\CRM_Contribute_Form_AdditionalPayment &$form) {
    $this->form = $form;
  }

  public function handle(): void {
    $element = $this->form->getElement('trxn_date');
    if ($element->getLabel() === ts('Refund Date')) {
      return;
    }

    $element->setLabel(ts('Date'));
  }

  public static function shouldHandle(\HTML_QuickForm $form, string $formName): bool {
    if ($formName !== 'CRM_Contribute_Form_AdditionalPayment' || !$form->elementExists('trxn_date')) {
      return FALSE;
    }

    return TRUE;
  }

}
