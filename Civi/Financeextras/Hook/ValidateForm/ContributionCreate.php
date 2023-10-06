<?php

namespace Civi\Financeextras\Hook\ValidateForm;

class ContributionCreate {

  /**
   * @param \CRM_Contribute_Form_Contribution $form
   * @param array $fields
   * @param array $errors
   */
  public function __construct(private \CRM_Contribute_Form_Contribution $form, private array &$fields, private array &$errors) {
  }

  public function handle() {
    $this->validatePaymentForm();
  }

  public function validatePaymentForm() {
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
    $addOrUpdate = ($form->getAction() & \CRM_Core_Action::ADD) || ($form->getAction() & \CRM_Core_Action::UPDATE);
    return $formName === "CRM_Contribute_Form_Contribution" && $addOrUpdate;
  }

}
