<?php

namespace Civi\Financeextras\Hook\BuildForm;

class RefundCreditNotePaymentInformation {

  /**
   * @param \CRM_Financial_Form_Payment $form
   */
  public function __construct(private \CRM_Financial_Form_Payment $form) {
  }

  public function handle() {
    if ($this->form->elementExists('check_number')) {
      $element = $this->form->getElement('check_number');
      $element->setLabel(ts('Cheque Number'));
    }
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
    return $formName === 'CRM_Financial_Form_Payment' && isset($_GET['formName']) && $_GET['formName'] === 'CreditNoteRefund';
  }

}
