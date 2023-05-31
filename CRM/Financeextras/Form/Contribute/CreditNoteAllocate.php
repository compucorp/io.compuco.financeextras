<?php

use Civi\Api4\Contribution;
use Civi\Api4\CreditNote;
use Civi\Api4\CreditNoteAllocation;
use Civi\Financeextras\Utils\CurrencyUtils;
use CRM_Certificate_ExtensionUtil as E;

/**
 * Credit Note allocation Form controller class.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Financeextras_Form_Contribute_CreditNoteAllocate extends CRM_Core_Form {

  /**
   * Credit Note to allocate credit to.
   *
   * @var int
   */
  public $crid;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('Allocate Credit Balance To Invoices'));

    $this->crid = CRM_Utils_Request::retrieve('crid', 'Positive', $this);
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm() {
    $currencies = array_column(CurrencyUtils::getCurrencies(), 'symbol', 'name');
    $creditNote = $this->getCreditNote();
    $contributions = $this->getContributions($creditNote);

    $this->assign('creditNote', $creditNote);
    $this->assign('contributions', $contributions);
    $this->assign('currencySymbol', $currencies[$creditNote['currency']]);

    foreach ($contributions as $contribution) {
      $this->add('text', 'item_ref[' . $contribution["id"] . ']', NULL, []);
      $this->add('number', 'item_amount[' . $contribution["id"] . ']', NULL, []);
    }

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'submit',
        'name' => E::ts('Allocate credit'),
      ],
    ]);

    parent::buildQuickForm();
  }

  /**
   * {@inheritDoc}
   */
  public function postProcess() {
    $values = $this->getSubmitValues();
    $creditNote = $this->getCreditNote();
    $amounts = array_filter($values['item_amount']);
    $references = array_filter($values['item_ref']);

    if (!$this->validateAllocation($amounts, $creditNote)) {
      return FALSE;
    }

    if (empty($amounts)) {
      CRM_Core_Session::setStatus('No amount was allocated.', '', 'info');
      return;
    }

    $contributionIds = array_keys($amounts);
    foreach ($contributionIds as $contributionId) {
      CreditNoteAllocation::allocate()
        ->setContributionId($contributionId)
        ->setCreditNoteId($this->crid)
        ->setReference($references[$contributionId])
        ->setTypeId($this->getAllocationTypeValueByName('invoice'))
        ->setAmount($amounts[$contributionId])
        ->setCurrency($creditNote['currency'])
        ->execute();
    }

  }

  /**
   * This enforces the rule to ensure
   * the allocates
   *
   * @param array $amounts
   *  Array of submitted amounts from user.
   * @param array $creditNote
   *  Array of credit note fields
   *
   * @return array|bool
   */
  public function validateAllocation($amounts, $creditNote) {
    $creditsToAllocate = array_sum($amounts);

    if ($creditsToAllocate > $creditNote['remaining_credit']) {
      CRM_Core_Session::setStatus('Amount to be refunded cannot exceed the remaining credit.', 'Error', 'error');
      return FALSE;
    }

    return TRUE;
  }

  private function getContributions(array $creditNote) {
    $contributions = Contribution::get()
      ->addWhere('contact_id', '=', $creditNote['contact_id'])
      ->addWhere('contribution_status_id:name', 'IN', ['Pending', 'Partially paid'])
      ->addWhere('currency', '=', $creditNote['currency'])
      ->execute()
      ->getArrayCopy();

    array_walk($contributions, function(&$contribution) use ($creditNote) {
      $paid_amount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id'], TRUE);
      $contribution['due_amount'] = CRM_Utils_Money::subtractCurrencies(
        $contribution['total_amount'],
        $paid_amount,
        $creditNote['currency']
      );
    });

    return $contributions;
  }

  /**
   * Returns the currrent credit note
   *
   * @return array
   *   Array of credit note fields and values.
   */
  private function getCreditNote() {
    return CreditNote::get()
      ->addWhere('id', '=', $this->crid)
      ->execute()
      ->first();
  }

  /**
   * Gets allocation type option value by name
   *
   * @param string $name
   *  The allocation type name.
   *
   * @return string|int
   *   The allocation type value.
   */
  private function getAllocationTypeValueByName($name) {
    $optionValues = \Civi\Api4\OptionValue::get()
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_allocation_type')
      ->addWhere('name', '=', $name)
      ->execute()
      ->first();

    return $optionValues['value'];
  }

}
