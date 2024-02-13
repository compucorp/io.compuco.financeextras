<?php

namespace Civi\Financeextras\Hook\ValidateForm;

class ContributionEdit {

  /**
   * @param \CRM_Contribute_Form_Contribution $form
   * @param array $fields
   * @param array $errors
   * @param string $formName
   */
  public function __construct(private \CRM_Contribute_Form_Contribution $form, private array &$fields, private array &$errors, private string $formName) {
  }

  public function handle() {
    $this->validateFinancialTypeUpdate();
  }

  public function validateFinancialTypeUpdate() {
    $contributionId = $this->form->_id;
    if (!empty($this->fields['financial_type_id'])) {
      $oldFinancialTypeId = \CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'financial_type_id');
      if ($oldFinancialTypeId == $this->fields['financial_type_id']) {
        return;
      }

      $this->errors['financial_type_id'] = 'One or more line items have a different financial type than the contribution. Editing the financial type is not yet supported in this situation.';
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
    $isUpdate = ($form->getAction() & \CRM_Core_Action::UPDATE);
    return $formName === "CRM_Contribute_Form_Contribution" && $isUpdate;
  }

}
