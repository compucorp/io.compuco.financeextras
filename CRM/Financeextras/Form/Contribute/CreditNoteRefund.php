<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Utils\CurrencyUtils;
use CRM_Financeextras_ExtensionUtil as E;

/**
 * Credit Note refund Form controller class.
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Financeextras_Form_Contribute_CreditNoteRefund extends CRM_Contribute_Form_AbstractEditPayment {

  /**
   * Credit Note to refund.
   *
   * @var int
   */
  public $crid;

  /**
   * Credit Note to refund.
   *
   * @var array
   */
  public $creditNote;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    CRM_Utils_System::setTitle('Record a cash refund');

    $this->crid = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->creditNote = $this->getCreditNote();
    $url = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $this->creditNote['contact_id'] . '&selectedChild=contribute', FALSE);
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
    parent::preProcess();
  }

  /**
   * {@inheritDoc}
   */
  public function buildQuickForm() {

    $this->addEntityRef('contact_id', ts('Contact'), [
      'entity' => 'Contact',
      'placeholder' => ts('- Contact -'),
      'select' => ['minimumInputLength' => 0],
      'api' => [
        'params' => [
          "is_active" => 1,
        ],
      ],
      'readonly' => TRUE,
      'class' => 'form-control',
    ], TRUE);

    $this->add(
      'select',
      'currency',
      '',
      array_combine(
        array_column(CurrencyUtils::getCurrencies(), 'name'),
        array_column(CurrencyUtils::getCurrencies(), 'symbol'),
      ),
      FALSE,
      ['disabled' => TRUE, 'class' => 'form-control']
    );

    $this->add(
      'number',
      'amount',
      ts('Refund Amount'),
      ['class' => 'form-control', 'min' => 0, 'step' => 0.01],
      TRUE
    );

    $this->add(
      'datepicker',
      'date',
      ts('Date'),
      NULL,
      TRUE,
      ['time' => FALSE]
    );

    $checkPaymentID = array_search('Check', CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'validate'));

    $this->add(
      'select',
      'payment_instrument_id',
      ts('Payment Method'),
      ['' => ts('- select -')] + CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'create'),
      TRUE,
      ['onChange' => "return showHideByValue('payment_instrument_id', '{$checkPaymentID}','checkNumber','table-row','select',false);", 'class' => 'form-control']
    );

    $this->add(
      'text',
      'trxn_id',
      ts('Transaction ID'),
      ['class' => 'form-control']
    );

    $this->add(
      'number',
      'fee_amount',
      ts('Fee Amount'),
      ['class' => 'form-control', 'min' => 0, 'step' => 0.01],
      TRUE
    );

    $this->add(
      'text',
      'reference',
      ts('Reference'),
      ['class' => 'form-control']
    );

    parent::buildQuickForm();

    $this->addButtons([
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'submit',
        'name' => E::ts('Create'),
      ],
    ]);
  }

  public function setDefaultValues() {
    if (empty($this->crid)) {
      return [];
    }

    $creditNote = $this->creditNote;
    $defaults = [
      'amount' => $creditNote['remaining_credit'],
      'contact_id' => $creditNote['contact_id'],
      'currency' => $creditNote['currency'],
      'date' => date('Y-m-d'),
    ];

    return $defaults;
  }

  public function addRules() {
    $this->addFormRule([$this, 'refundRule']);
  }

  /**
   *
   * @param array $values
   *
   * @return array|bool
   */
  public function refundRule($values) {
    $errors = [];
    if ($values['amount'] > $this->creditNote['remaining_credit']) {
      $errors['amount'] = ts('Amount to be refunded cannot exceed the remaining credit');
    }

    return $errors ?: TRUE;
  }

  /**
   * {@inheritDoc}
   */
  public function postProcess() {
    try {
      $values = $this->getSubmitValues();

      \Civi\Api4\CreditNote::refund(FALSE)
        ->setId($this->crid)
        ->setAmount(round($values['amount'], 2))
        ->setReference($values['reference'])
        ->setDate($values['date'])
        ->setPaymentParam([
          'payment_instrument_id' => $values['payment_instrument_id'],
          'credit_card_type' => $values['credit_card_type'],
          'pan_truncation' => $values['pan_truncation'],
          'trxn_id' => $values['trxn_id'],
          'fee_amount' => $values['fee_amount'],
          'check_number' => $values['check_number'],
        ])
        ->execute()
        ->first();

      CRM_Core_Session::setStatus('Credit note refund created successfully.', '', 'success');
      $url = CRM_Utils_System::url('civicrm/contact/view',
        ['cid' => $this->creditNote['contact_id'], 'selectedChild' => 'contribute']
      );
      CRM_Utils_System::redirect($url);
    }
    catch (\Throwable $th) {
      CRM_Core_Session::setStatus(E::ts($th->getMessage()), ts('Error Creating credit note refund'), 'error');
    }
  }

  /**
   * Returns the currrent credit note
   *
   * @return array
   *   Array of credit note fields and values.
   */
  private function getCreditNote() {
    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $this->crid)
      ->execute()
      ->first();

    if (empty($creditNote)) {
      throw new CRM_Core_Exception("Credit note with the given id doesn't exist");
    }

    return $creditNote;
  }

}
