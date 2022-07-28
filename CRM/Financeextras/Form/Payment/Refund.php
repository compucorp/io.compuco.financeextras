<?php

use CRM_Finananceextras_ExtensionUtil as E;

/**
 * Page for redirecting to credit card refund form.
 */
class CRM_Financeextras_Form_Payment_Refund extends CRM_Core_Form {

  /**
   * This function is called prior to building and submitting the form
   */
  public function preProcess() {
    $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $entityFinancialTrxns = civicrm_api3('EntityFinancialTrxn', 'get', [
      'sequential' => 1,
      'entity_id' => $contributionId,
      'entity_table' => 'civicrm_contribution',
      'options' => ['limit' => 0],
    ])['values'];
    $paymentInfos = [];
    $loopCount = 0;
    foreach ($entityFinancialTrxns as $entity) {
      $financialTrxns = civicrm_api3('FinancialTrxn', 'get', [
        'id' => $entity['financial_trxn_id'],
        'is_payment' => 1,
      ])['values'];
      if ($loopCount == 0) {
        $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
          'return' => ['name'],
          'id' => $financialTrxns[$entity['financial_trxn_id']]['payment_processor_id'],
        ]);
      }
      foreach ($financialTrxns as $i => $trxn) {
        $chargeID = $trxn['trxn_id'];
        if (!isset($trxn['payment_processor_id']) || $trxn['payment_processor_id'] === "") {
          return;
        }
        $refundableBalance = civicrm_api3('PaymentProcessor', 'get_refundable_balance', [
          'payment_processor_id' => $trxn['payment_processor_id'],
          'trxn_id' => $chargeID,
        ])['values'][0];

        $availableAmount = $trxn['total_amount'] - $refundableBalance;

        $paymentInfos[] = [
          'date' => date('d-m-Y', strtotime($trxn['trxn_date'])),
          'amount' => $trxn['total_amount'],
          'available_amount' => $availableAmount,
          'paymentProcessor' => $paymentProcessor['name'],
          'transactionId' => $trxn['trxn_id'],
          'financialTrxnId' => $entity['financial_trxn_id'],
          'currency' => $trxn['currency'],
          'paymentProcessorId' => $trxn['payment_processor_id'],
        ];
      }
      $loopCount = $loopCount + 1;
    }
    $this->assign('paymentInfos', $paymentInfos);
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
    $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $contribution = civicrm_api3('Contribution', 'getsingle', [
      'id' => $contributionId,
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
    $contributionId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $paymentRefundPermission = new \Civi\Financeextras\Payment\Refund($contributionId);
    if (!$paymentRefundPermission->contactHasRefundPermission()) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
      return;
    }
    Civi::resources()->addStyleFile('io.compuco.financeextras', 'css/custom-civicrm.css');
    Civi::resources()->addScriptFile('io.compuco.financeextras', 'js/custom.js');
    $this->setFields();
    $this->add('hidden', 'contribution_id', $contributionId);
    $this->add('hidden', 'available_amount',);
    $this->add('hidden', 'trxn_id',);
    $this->add('hidden', 'payment_processor_id',);

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
  public static function formRule($fields, $files, $self) {
    $errors = [];
    if ($fields['amount'] <= 0) {
      $errors['amount'] = ts('Please enter valid refund amount.');
    }
    elseif ($fields['amount'] > $fields['available_amount']) {
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
      'payment_processor_id' => $vals['payment_processor_id'],
      'contribution_id' => $vals['contribution_id'],
      'available_amount' => $vals['available_amount'],
      'trxn_id' => $vals['trxn_id'],
      'contact' => $vals['contact'],
      'reason' => $vals['reason'],
      'amount' => $vals['amount'],
      'currency' => $vals['currency'],
    ])['values'];
  }

}
