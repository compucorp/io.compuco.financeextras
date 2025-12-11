<?php

use Civi\Api4\CreditNote;
use Civi\Api4\Contribution;
use Civi\Financeextras\Utils\OverpaymentUtils;
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Form to allocate overpayment to a new credit note.
 */
class CRM_Financeextras_Form_Contribute_OverpaymentAllocate extends CRM_Core_Form {

  /**
   * Contribution ID.
   *
   * @var int
   */
  protected $contributionId;
  /**
   * Contribution data.
   *
   * @var array
   */
  protected $contribution;
  /**
   * Overpayment amount.
   *
   * @var float
   */
  protected $overpaymentAmount;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    $this->contributionId = CRM_Utils_Request::retrieve('contribution_id', 'Positive', $this, TRUE);

    // Validate eligibility.
    if (!OverpaymentUtils::isEligibleForOverpaymentAllocation($this->contributionId)) {
      throw new CRM_Core_Exception(E::ts('This contribution is not eligible for overpayment allocation.'));
    }

    $this->contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->addSelect('*', 'contact_id.display_name')
      ->execute()
      ->first();

    $this->overpaymentAmount = OverpaymentUtils::getOverpaymentAmount($this->contributionId);

    CRM_Utils_System::setTitle(E::ts('Allocate overpayment to credit note'));
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm() {
    $currencySymbol = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', $this->contribution['currency'], 'symbol', 'name');
    $formattedAmount = CRM_Utils_Money::format($this->overpaymentAmount, $this->contribution['currency']);

    $this->assign('message', E::ts('Allocate an overpaid amount to a new credit note. The credit can then be applied to future invoices. This cannot be undone.'));
    $this->assign('overpaymentAmount', $formattedAmount);
    $this->assign('contactName', $this->contribution['contact_id.display_name']);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    parent::buildQuickForm();
  }

  /**
   * {@inheritDoc}
   */
  public function postProcess() {
    try {
      $result = CreditNote::allocateOverpayment(FALSE)
        ->setContributionId($this->contributionId)
        ->execute()
        ->first();

      CRM_Core_Session::setStatus(
        E::ts('Overpayment allocated to credit note "%1" successfully.', [1 => $result['cn_number']]),
        E::ts('Success'),
        'success'
      );
    }
    catch (\Throwable $th) {
      CRM_Core_Session::setStatus(
        E::ts('Error allocating overpayment: %1', [1 => $th->getMessage()]),
        E::ts('Error'),
        'error'
      );
    }
  }

}
