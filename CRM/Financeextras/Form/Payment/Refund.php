<?php

use CRM_Finananceextras_ExtensionUtil as E;

/**
 * Page for redirecting to credit card refund form.
 */
class CRM_Financeextras_Form_Payment_Refund extends CRM_Core_Form {

  /**
   * @var int
   *   Contribution id for adding submit refund button.
   */
  private $contributionID;

  /**
   * @var int
   *   Available amount to display in payment info table.
   */
  private $availableAmount;

  /**
   * @var int
   *   Payment processor id
   */
  private $paymentProcessorId;

  /**
   * @var string
   *   Charge id
   */
  private $chargeID;

  /**
   * This function is called prior to building and submitting the form
   */
  public function preProcess() {
    $this->contributionID = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $paymentRefundPermission = new \Civi\Financeextras\Payment\Refund($this->contributionID);
    if (!$paymentRefundPermission->contactHasRefundPermission()) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      return;
    }
    $paymentInfos = [];
    $this->availableAmount = 0;
    $entityFinancialTrxns = $this->getEntityFinancialTransaction($this->contributionID);
    $paymentProcessor = $this->getPaymentProcessorNameByfinanceTrxnId($entityFinancialTrxns[0]['financial_trxn_id']);
    $refundAmountMethod = TRUE;
    $processor = Civi\Payment\System::singleton()->getById($paymentProcessor['payment_processor_id']);
    if (!method_exists($processor, 'getRefundedAmountByChargeId')) {
      $refundAmountMethod = FALSE;
    }

    foreach ($entityFinancialTrxns as $entity) {
      $financialTrxns = civicrm_api3('FinancialTrxn', 'get', [
        'id' => $entity['financial_trxn_id'],
        'is_payment' => 1,
      ])['values'];
      foreach ($financialTrxns as $i => $trxn) {
        $this->chargeID = $trxn['trxn_id'];
        $this->paymentProcessorId = $trxn['payment_processor_id'];
        if (!isset($trxn['payment_processor_id']) || $trxn['payment_processor_id'] === "") {
          return;
        }
        if (!method_exists($processor, 'getRefundedAmountByChargeId')) {
          $this->availableAmount = $trxn['total_amount'];
        }
        else {
          $refundedAmount = $this->getRefundedAmount($this->chargeID, $this->paymentProcessorId);
          $this->availableAmount = $trxn['total_amount'] - $refundedAmount;
        }

        $paymentInfos[] = [
          'date' => date('d-m-Y', strtotime($trxn['trxn_date'])),
          'amount' => $trxn['total_amount'],
          'available_amount' => $this->availableAmount,
          'paymentProcessor' => $paymentProcessor['payment_processor_name'],
          'transactionId' => $trxn['trxn_id'],
          'financialTrxnId' => $entity['financial_trxn_id'],
          'currency' => $trxn['currency'],
          'paymentProcessorId' => $this->paymentProcessorId,
        ];
      }
    }
    $this->assign('refundAmountMethod', $refundAmountMethod);
    $this->assign('paymentInfos', $paymentInfos);

  }

  /**
   * Gets the payment processor name.
   */
  public function getPaymentProcessorNameByfinanceTrxnId(int $financeTrxnId) {
    $financialTrxn = civicrm_api3('FinancialTrxn', 'get', [
      'id' => $financeTrxnId,
      'is_payment' => 1,
    ])['values'];
    $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
      'return' => ['name'],
      'id' => $financialTrxn[$financeTrxnId]['payment_processor_id'],
    ]);
    $paymentProcessorName = $paymentProcessor['name'];

    return [
      'payment_processor_name' => $paymentProcessorName,
      'payment_processor_id' => $financialTrxn[$financeTrxnId]['payment_processor_id'],
    ];
  }

  /**
   * Gets the payment processor name.
   */
  public function checkRefundMethodExist(int $financeTrxnId) {
    $financialTrxn = civicrm_api3('FinancialTrxn', 'get', [
      'id' => $financeTrxnId,
      'is_payment' => 1,
    ])['values'];
    $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
      'return' => ['name'],
      'id' => $financialTrxn[$financeTrxnId]['payment_processor_id'],
    ]);
    $paymentProcessorName = $paymentProcessor['name'];

    return $paymentProcessorName;
  }

  /**
   * Gets the financial transactions.
   */
  public function getEntityFinancialTransaction(int $id) {
    $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', [
      'sequential' => 1,
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution',
      'options' => ['limit' => 0],
    ])['values'];

    return $entityFinancialTrxns;
  }

  /**
   * Gets the refunded amount.
   */
  private function getRefundedAmount(string $chargeID, int $paymentProcessorId) {
    $refundedAmount = civicrm_api3('PaymentProcessor', 'get_refunded_amount', [
      'payment_processor_id' => $paymentProcessorId,
      'trxn_id' => $chargeID,
    ])['values'][0];

    return $refundedAmount;
  }

  /**
   * The _fields var can be used by sub class to set/unset/edit the
   * form fields based on their requirement
   */
  public function setFields() {
    $reasons = [
      'duplicate' => "Duplicate",
      "fraudulent" => "Fraudulent",
      "requested_by_customer" => "Requested by customer",
    ];
    $contribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $this->contributionID,
      'return' => ['contact_id'],
    ]);
    $contactId = $contribution['contact_id'];

    $contact = civicrm_api3('Contact', 'getsingle', [
      'contact_id' => $contactId,
      'return' => ['display_name'],
    ]);
    $contactName = $contact['display_name'];
    $this->_fields = [
      'contact' => [
        'type' => 'text',
        'label' => ts('Contact'),
        'attributes' => ['class' => 'huge', 'value' => $contactName, 'disabled' => 'disabled'],
        'required' => FALSE,
      ],

      'currency' => [
        'type' => 'text',
        'label' => ts('Contact'),
        'attributes' => ['disabled' => 'disabled'],
        'required' => FALSE,
      ],

      'amount' => [
        'type' => 'text',
        'label' => ts('Refund Amount'),
        'required' => TRUE,
      ],

      'reason' => [
        'type' => 'select',
        'label' => ts('Reason'),
        'attributes' => ['' => '- ' . ts('select reason') . ' -'] + $reasons,
        'extra' => ['class' => 'huge crm-select2'],

      ],
    ];
  }

  /**
   * Will be called prior to outputting html (and prior to buildForm hook)
   */
  public function buildQuickForm() {
    Civi::resources()->addStyleFile('io.compuco.financeextras', 'css/custom-civicrm.css');
    Civi::resources()->addScriptFile('io.compuco.financeextras', 'js/custom.js');
    $this->setFields();
    foreach ($this->_fields as $field => $values) {
      if (!empty($this->_fields[$field])) {
        $attribute = $values['attributes'] ?? NULL;
        $required = !empty($values['required']);

        if ($values['type'] === 'select' && empty($attribute)) {
          $this->addSelect($field, ['entity' => 'activity'], $required);
        }
        elseif ($values['type'] === 'entityRef') {
          $this->addEntityRef($field, $values['label'], $attribute, $required);
        }
        else {
          $this->add($values['type'], $field, $values['label'], $attribute, $required, CRM_Utils_Array::value('extra', $values));
        }
      }
    }
    $this->addFormRule(['CRM_Financeextras_Form_Payment_Refund', 'formRule'], $this);

    $this->addButtons([
      [
        'type' => 'upload',
        'name' => ts('Create'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  /**
   * Global form rule.
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public function formRule($fields, $files, $self) {
    $errors = [];
    if ($fields['amount'] <= 0) {
      $errors['amount'] = ts('Please enter valid refund amount.');
    }
    elseif ($fields['amount'] > $self->availableAmount) {
      $errors['amount'] = ts('You cannot refund more than the original payment amount.');
    }
    if ($fields['reason'] == "") {
      $errors['reason'] = ts('Please enter refund reason.');
    }
    return $errors;
  }

  /**
   * Calls after form is successfully submitted.
   */
  public function postProcess($params = NULL) {
    // Gets the submitted values as an array.
    $vals = $this->controller->exportValues($this->_name);
    civicrm_api3('PaymentProcessor', 'refund', [
      'payment_processor_id' => $this->paymentProcessorId,
      'contribution_id' => $this->contributionID,
      'available_amount' => $this->availableAmount,
      'trxn_id' => $this->chargeID,
      'contact' => $vals['contact'],
      'reason' => $vals['reason'],
      'amount' => $vals['amount'],
      'currency' => $vals['currency'],
    ])['values'];
  }

}
