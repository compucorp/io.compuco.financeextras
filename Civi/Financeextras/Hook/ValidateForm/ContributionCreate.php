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
    $this->updateTotalAmountFromLineTotal();
    $this->validatePaymentForm();
    $this->validateConsistentIncomeAccountOwners();
    $this->removeInvalidSoftCreditErrors();
    $this->updateNonDeductibleAmountFromLineTotal();
  }

  public function validatePaymentForm() {
    if (!empty($this->fields['fe_record_payment_check']) && empty($this->fields['fe_record_payment_amount'])) {
      $this->errors['fe_record_payment_amount'] = ts('Payment amount is required');
    }
  }

  /**
   * Computes credit card contribution total from line items.
   *
   * Credit card contribution is unaware of the line items
   * so it cant compute total value from them, `
   * to avoid having empty total when line item is used,
   * we manually set the contribution total to the line item total
   * before form submission.
   */
  public function updateTotalAmountFromLineTotal() {
    if ($this->form->_mode !== 'live') {
      return;
    }

    if (empty($this->fields['total_amount']) && !empty($this->fields['fe_record_payment_amount'])) {
      $data = &$this->form->controller->container();
      $total = array_sum($data['values']['Contribution']['item_line_total']) + array_sum($data['values']['Contribution']['item_tax_amount']);
      $data['values']['Contribution']['total_amount'] = $total ?? $this->fields['fe_record_payment_amount'];
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
    $addOrUpdate = ($form->getAction() & \CRM_Core_Action::ADD) || ($form->getAction() & \CRM_Core_Action::UPDATE);
    return $formName === "CRM_Contribute_Form_Contribution" && $addOrUpdate;
  }

  /**
   * Remove invalid errors for soft credit fields.
   *
   * Contribution is unaware of the line items and it cant compute total amount from them
   * so total amount is 0 and when civi compares the soft credit amount with total amount of 0 it throws error that
   * soft credit amount cannot be more than total amount.
   */
  private function removeInvalidSoftCreditErrors(): void {
    if (empty($this->fields['total_amount']) && !empty($this->fields['fe_record_payment_amount'])) {
      foreach ($this->fields['soft_credit_amount'] as $key => $val) {
        if ($this->form->getElementError("soft_credit_amount[$key]") !== NULL
          && !empty($this->fields['soft_credit_amount'][$key])
          && \CRM_Utils_Rule::cleanMoney($this->fields['soft_credit_amount'][$key]) <= \CRM_Utils_Rule::cleanMoney($this->fields['fe_record_payment_amount'])
        ) {
          $this->form->setElementError("soft_credit_amount[$key]");
        }
      }
    }

  }

  private function updateNonDeductibleAmountFromLineTotal(): void {
    try {
      if ($this->form->_mode !== 'live') {
        return;
      }

      $data = &$this->form->controller->container();

      if ((isset($data['values']['Contribution']['non_deductible_amount']) && (!empty($data['values']['Contribution']['non_deductible_amount']))) ||
        isset($data['values']['Contribution']['price_set_id']) || isset($data['values']['Contribution']['priceSetId'])
      ) {
        return;
      }

      $financialType     = new \CRM_Financial_DAO_FinancialType();
      $financialType->id = $data['values']['Contribution']['financial_type_id'];
      $financialType->find(TRUE);

      if (!$financialType->is_deductible && !empty($data['values']['Contribution']['item_line_total']) && !empty($data['values']['Contribution']['item_tax_amount'])) {
        $total                                                   = array_sum($data['values']['Contribution']['item_line_total']) + array_sum($data['values']['Contribution']['item_tax_amount']);
        $data['values']['Contribution']['non_deductible_amount'] = $total ?? $this->fields['fe_record_payment_amount'];
      }
    }
    catch (\Throwable $e) {
    }
  }

}
