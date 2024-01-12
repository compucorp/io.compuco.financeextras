<?php

use Civi\Api4\Contribution;
use Civi\Api4\CreditNote;
use Civi\Api4\CreditNoteAllocation;
use Civi\Financeextras\Utils\CurrencyUtils;
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Credit Note allocation Form controller class.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Financeextras_Form_Contribute_CreditNoteAllocate extends CRM_Core_Form {

  /**
   * ID of the credit Note to allocate credit to.
   *
   * @var int
   */
  public $crid;

  /**
   * Credit Note to allocate credit to.
   *
   * @var array
   */
  public $creditNote;

  /**
   * If completed contributions should be completed
   *
   * @var bool
   */
  public $includeCompleted;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('Allocate Credit Balance To Invoices'));

    $this->crid = CRM_Utils_Request::retrieve('crid', 'Positive', $this);
    $this->creditNote = $this->getCreditNote();
    $this->includeCompleted = CRM_Utils_Request::retrieve('completed_contribution', 'Positive', $this, 0) === 1;

    $url = CRM_Utils_System::url('civicrm/contact/view',
      ['reset' => 1, 'cid' => $this->creditNote['contact_id'], 'selectedChild' => 'contribute']
    );
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm() {
    $currencies = array_column(CurrencyUtils::getCurrencies(), 'symbol', 'name');
    $contributions = $this->getContributions($this->includeCompleted);

    $this->assign('creditNote', $this->creditNote);
    $this->assign('contributions', $contributions);
    $this->assign('currencySymbol', $currencies[$this->creditNote['currency']]);

    $this->addCheckBox('incl_completed', '', ['Yes' => TRUE]);

    foreach ($contributions as $contribution) {
      $this->add('text', 'item_ref[' . $contribution["id"] . ']', NULL, []);
      $this->add('number', 'item_amount[' . $contribution["id"] . ']', NULL, ['min' => 0, 'step' => 0.01]);
    }

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => E::ts('Skip'),
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
    $amounts = array_filter($values['item_amount'] ?? []);
    $references = array_filter($values['item_ref'] ?? []);

    if (!$this->validateAllocation($amounts)) {
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
        ->setAmount(floatval($amounts[$contributionId]))
        ->setCurrency($this->creditNote['currency'])
        ->execute();
    }

    CRM_Core_Session::setStatus(ts('Credits allocated successfully.'), ts('Success'), 'success');
    $url = CRM_Utils_System::url('civicrm/contact/view',
      ['reset' => 1, 'cid' => $this->creditNote['contact_id'], 'selectedChild' => 'contribute']
    );
    CRM_Utils_System::redirect($url);

    return;
  }

  /**
   * This enforces the rule to ensure
   * the allocation is valid
   *
   * @param array $amounts
   *  Array of submitted amounts from user.
   *
   * @return array|bool
   */
  public function validateAllocation($amounts) {
    if (!empty(array_filter($amounts, fn($n) => $n <= 0))) {
      CRM_Core_Session::setStatus(ts('Amount to be refunded must be greater than zero.'), ts('Error'), 'error');
      return FALSE;
    }

    $creditsToAllocate = array_sum($amounts);

    if ($creditsToAllocate > $this->creditNote['remaining_credit']) {
      CRM_Core_Session::setStatus(ts('Amount to be refunded cannot exceed the remaining credit.'), ts('Error'), 'error');
      return FALSE;
    }

    return TRUE;
  }

  private function getContributions(bool $includeCompleted = FALSE) {
    $statuses = ['Pending', 'Partially paid'];

    if ($includeCompleted) {
      array_push($statuses, 'Completed');
    }

    $contributions = Contribution::get(FALSE)
      ->addWhere('contact_id', '=', $this->creditNote['contact_id'])
      ->addWhere('contribution_status_id:name', 'IN', $statuses)
      ->addWhere('currency', '=', $this->creditNote['currency'])
      ->execute()
      ->getArrayCopy();

    array_walk($contributions, function(&$contribution) {
      $contribution['due_amount'] = CRM_Utils_Money::subtractCurrencies(
        $contribution['total_amount'],
        $contribution['paid_amount'],
        $this->creditNote['currency']
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
    return CreditNote::get(FALSE)
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
    $optionValues = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_allocation_type')
      ->addWhere('name', '=', $name)
      ->execute()
      ->first();

    return $optionValues['value'];
  }

}
