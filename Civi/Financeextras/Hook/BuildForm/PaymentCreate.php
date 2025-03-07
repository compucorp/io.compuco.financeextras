<?php

namespace Civi\Financeextras\Hook\BuildForm;

/**
 * Sets the payment method to the default one instead of contribution payment method.
 */
class PaymentCreate {

  /**
   * @var \CRM_Contribute_Form_AdditionalPayment
   *   Form object that is being altered.
   */
  private \CRM_Contribute_Form_AdditionalPayment $form;

  /**
   * PaymentCreate constructor.
   *
   * @param \CRM_Contribute_Form_AdditionalPayment $form
   */
  public function __construct(\CRM_Contribute_Form_AdditionalPayment &$form) {
    $this->form = $form;
  }

  public function handle(): void {
    $defaultPaymentMethod = key(\CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    if ($defaultPaymentMethod) {
      $this->form->getElement('payment_instrument_id')->setValue($defaultPaymentMethod);
    }
  }

  public static function shouldHandle($form, $formName) {
    if ($formName !== 'CRM_Contribute_Form_AdditionalPayment' || !$form->elementExists('payment_instrument_id')) {
      return FALSE;
    }

    return TRUE;
  }

}
