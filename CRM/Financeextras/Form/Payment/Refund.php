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
   * @var array
   *   Available amount to display in payment info table.
   */
  private $availableAmount;

  /**
   * @var array
   *   Payment processor with keys id, name, is_test
   */
  private $paymentProcessor;

  /**
   * @var array
   *    Payment Transactions of A Contribution It Include main as well as refund transactions
   */
  private $paymentTransactions;

  /**
   * @var string
   *   Charge id
   */
  private $chargeID;

  /**
   * @var string
   *   Refund Status from Payment Processor
   */
  private $refundStatus;

  /**
   * @var int
   *   Main transaction id
   */
  private $mainTransactionId;

  /**
   * Prevent people double-submitting the form (e.g. by double-clicking).
   * https://lab.civicrm.org/dev/core/-/issues/1773
   *
   * @var bool
   */
  public $submitOnce = TRUE;

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
    $this->paymentTransactions = civicrm_api3('Payment', 'get', [
      'contribution_id' => $this->contributionID,
      'status_id' => 1,
    ])['values'];

    $refundAmountMethod = TRUE;
    foreach ($this->paymentTransactions as $trxn) {
      // Ignore if payment processor ID is not present.
      // This is the edge case for the transaction that may have been paid
      // offline e.g. cash or cheque.
      if (empty($trxn['payment_processor_id'])) {
        continue;
      }

      $this->mainTransactionId[$trxn['id']] = $trxn['id'];
      $this->availableAmount[$trxn['id']] = 0;
      $this->chargeID[$trxn['id']] = $trxn['trxn_id'];
      $this->paymentProcessor = $this->getPaymentProcessorNameById($trxn['payment_processor_id']);
      $processor = Civi\Payment\System::singleton()->getById($this->paymentProcessor['id']);
      if (!method_exists($processor, 'getRefundedAmountByChargeId')) {
        $this->availableAmount = $trxn['total_amount'];
        $refundAmountMethod = FALSE;
      }
      else {
        $refundedAmount = $this->getRefundedAmount($this->chargeID[$trxn['id']], $this->paymentProcessor['id'], $this->mainTransactionId[$trxn['id']], $trxn['currency']);
        $this->availableAmount[$trxn['id']] = $trxn['total_amount'] - $refundedAmount;
      }
      if (!isset($this->paymentProcessor['id']) || $this->paymentProcessor['id'] === "") {
        return;
      }
      $paymentInfos[] = [
        'date' => date('d-m-Y', strtotime($trxn['trxn_date'])),
        'amount' => $trxn['total_amount'],
        'available_amount' => $this->availableAmount[$trxn['id']],
        'paymentProcessor' => $this->paymentProcessor['name'],
        'transactionId' => $trxn['trxn_id'],
        'financialTrxnId' => $trxn['id'],
        'currency' => $trxn['currency'],
        'paymentProcessorId' => $this->paymentProcessor['id'],
      ];
    }
    $this->assign('refundAmountMethod', $refundAmountMethod);
    $this->assign('paymentInfos', $paymentInfos);
  }

  /**
   * Gets the payment processor name.
   */
  public function getPaymentProcessorNameById(int $Id) {
    return civicrm_api3('PaymentProcessor', 'getsingle', [
      'return' => ['name', 'is_test', 'id', 'payment_instrument_id'],
      'id' => $Id,
    ]);
  }

  /**
   * Gets the refunded amount.
   */
  private function getRefundedAmount(string $chargeID, int $paymentProcessorID, int $transactionID, string $currency) {
    return civicrm_api3('PaymentProcessor', 'get_refunded_amount', [
      'payment_processor_id' => $paymentProcessorID,
      'trxn_id' => $chargeID,
      'financial_trxn_id' => $transactionID,
      'currency' => $currency,
    ])['values'][0];
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
        'required' => TRUE,

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
    $this->addFormRule([$this, 'formRule']);

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
   *
   * @return bool|array
   *   true if no errors, else array of errors
   */
  public function formRule($fields) {
    $errors = [];
    if ($fields['amount'] <= 0) {
      $errors['amount'] = ts('Please enter valid refund amount.');
    }
    elseif ($fields['amount'] > $this->availableAmount[$fields['payment_row']]) {
      $errors['amount'] = ts('You cannot refund more than the available amount .');
    }
    if ($fields['reason'] == "") {
      $errors['reason'] = ts('Please enter refund reason.');
    }
    if (count($errors) === 0) {
      $this->triggerRefund($errors, $fields);
    }

    return $errors;
  }

  /**
   * Trigger Refund action of Payment Processor
   */
  private function triggerRefund(&$errors, $vals) {
    try {
      $refundResult = civicrm_api3('PaymentProcessor', 'refund', [
        'payment_processor_id' => $this->paymentProcessor['id'],
        'contribution_id' => $this->contributionID,
        'available_amount' => $this->availableAmount[$vals['payment_row']],
        'trxn_id' => $this->chargeID[$vals['payment_row']],
        'contact' => $vals['contact'],
        'reason' => $vals['reason'],
        'amount' => $vals['amount'],
        'currency' => $vals['currency'],
        'is_test' => $this->paymentProcessor['is_test'],
      ])['values'];

      $this->refundStatus = $refundResult[0];
    }
    catch (\Exception $e) {
      $errors['amount'] = $e->getMessage();
    }
  }

  /**
   * Calls after form is successfully submitted.
   */
  public function postProcess($params = NULL) {
    // Gets the submitted values as an array.

    $vals = $this->controller->exportValues($this->_name);
    if ($this->refundStatus['refund_status'] == "Completed") {
      $this->createFinancialTrxnRefund($vals);
    }
  }

  /**
   * Creates financial transaction entries for refund
   */
  public function createFinancialTrxnRefund($vals) {
    $financialTrxnId = CRM_Utils_Request::retrieve('payment_row', 'Positive', $this);
    $eftParams = [
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $this->mainTransactionId[$financialTrxnId],
      'return' => ['entity', 'amount', 'entity_id', 'financial_trxn_id.check_number'],
    ];
    $entity = civicrm_api3('EntityFinancialTrxn', 'getsingle', $eftParams);
    $paymentParams = [
      'total_amount' => -$vals['amount'],
      'contribution_id' => (int) $entity['entity_id'],
      'is_send_contribution_notification' => FALSE,
      'trxn_date' => 'now',
      'trxn_id' => $this->refundStatus['refund_id'],
      'payment_instrument_id' => $this->paymentProcessor['payment_instrument_id'],
      'check_number' => $entity['financial_trxn_id.check_number'] ?? NULL,
      'payment_processor_id' => $this->paymentProcessor['id'] ?? NULL,
      'payment_processor_name' => $this->paymentProcessor['name'] ?? NULL,
    ];

    civicrm_api3('Payment', 'create', $paymentParams);
    CRM_Core_Session::setStatus(ts('Refund has been requested successfully.'), ts('Success'), 'success');
  }

}
