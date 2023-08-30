<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use Civi\Financeextras\Utils\OptionValueUtils;

class MembershipCreate {

  /**
   * @param \CRM_Member_Form_Membership $form
   * @param array $fields
   * @param array $errors
   */
  public function __construct(private \CRM_Member_Form_Membership $form, private array &$fields, private array &$errors) {
  }

  public function handle() {
    $this->validatePaymentForm();
  }

  public function validatePaymentForm() {
    if (empty($this->fields['record_contribution'])) {
      // Don't do anything if user will not be recording contribution
      return;
    }

    if ($this->fields['contribution_type_toggle'] == 'payment_plan') {
      // Don't do anything if membership is paid for using the payment plan option
      return;
    }

    if (empty($this->fields['fe_record_payment_check'])) {
      $data = &$this->form->controller->container();
      // Before the form is submitted, we ensure that if the "Record Payment" box is unchecked,
      // payment will not be recorded against the contribution.
      $data['values']['Membership']['contribution_status_id'] = OptionValueUtils::getValueForOptionValue('contribution_status', 'Pending');
    }

    if (!empty($this->fields['fe_record_payment_check']) && empty($this->fields['fe_record_payment_amount'])) {
      $this->errors['fe_record_payment_amount'] = ts('Payment amount is required');
    }
  }

  /**
   * Checks if the hook should run.
   *
   * @param CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName) {
    return $formName === "CRM_Member_Form_Membership" && ($form->_action & \CRM_Core_Action::ADD);
  }

}
