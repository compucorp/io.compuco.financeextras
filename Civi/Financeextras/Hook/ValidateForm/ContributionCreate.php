<?php

namespace Civi\Financeextras\Hook\ValidateForm;

use Civi\Financeextras\Hook\ValidateForm\OwnerOrganizationRetriever as OwnerOrganizationRetriever;

class ContributionCreate {

  /**
   * @param \CRM_Contribute_Form_Contribution $form
   * @param array $fields
   * @param array $errors
   * @param string $formName
   */
  public function __construct(private \CRM_Contribute_Form_Contribution $form, private array &$fields, private array &$errors, private string $formName) {
  }

  public function handle() {
    $this->validatePaymentForm();
    $this->validateConsistentIncomeAccountOwners();
  }

  public function validatePaymentForm() {
    if (!empty($this->fields['fe_record_payment_check']) && empty($this->fields['fe_record_payment_amount'])) {
      $this->errors['fe_record_payment_amount'] = ts('Payment amount is required');
    }
  }

  /**
   * Validates if the selected contribution
   * financial type owner, or any of the
   * added line item financial type owners matches
   * the original contribution financial type owner.
   *
   * This is to prevent users from changing the owner
   * organization of the contribution, or form them
   * to have line items with inconsistent owners.
   *
   * @return void
   */
  private function validateConsistentIncomeAccountOwners() {
    if ($this->form->getAction() != \CRM_Core_Action::UPDATE) {
      return;
    }

    $contributionId = $this->form->_id;
    $selectedFinancialTypeId = $this->fields['financial_type_id'];
    $currentFinancialTypeId = civicrm_api3('Contribution', 'getvalue', [
      'return' => 'financial_type_id',
      'id' => $contributionId,
    ]);

    $formFinancialTypeIds = [$currentFinancialTypeId, $selectedFinancialTypeId];
    if (!empty($this->fields['item_financial_type_id'])) {
      $lineItemsFinancialTypes = array_filter($this->fields['item_financial_type_id']);
      $formFinancialTypeIds = array_merge($formFinancialTypeIds, $lineItemsFinancialTypes);
    }

    $formFinancialTypeOwners = OwnerOrganizationRetriever::getFinancialTypesOwnerOrganizationIds($formFinancialTypeIds);
    if (count($formFinancialTypeOwners) > 1) {
      $this->errors['financial_type_id'] = 'It is not possible to make the proposed changes to this contribution as the owner organisation of the contribution is not connected to the financial type of the proposed new line items. Please update the financial types.';
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
    return $formName === "CRM_Contribute_Form_Contribution";
  }

}
