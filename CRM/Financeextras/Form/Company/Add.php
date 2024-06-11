<?php

class CRM_Financeextras_Form_Company_Add extends CRM_Core_Form {

  /**
   * Company id in case of update
   *
   * @var int
   */
  private $id;

  /**
   * @inheritdoc
   */
  public function preProcess() {
    $this->id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $mode = 'New';
    if ($this->id) {
      $mode = 'Edit';
    }

    $title = $mode . ' Company';
    CRM_Utils_System::setTitle(ts($title));

    $url = CRM_Utils_System::url('civicrm/admin/financeextras/company', 'reset=1');
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext($url);
  }

  public function buildQuickForm() {
    $this->addEntityRef('contact_id', ts('Organisation'), [
      'api' => ['params' => ['contact_type' => 'Organization']],
      'select' => ['minimumInputLength' => 0],
      'placeholder' => ts('Select Organisation'),
    ], TRUE);

    $this->addEntityRef('invoice_template_id', ts('Invoice Template'), [
      'entity' => 'MessageTemplate',
      'api' => [
        'search_field' => 'msg_title',
        'label_field' => 'msg_title',
        'params' => [
          'is_default' => 1,
        ],
      ],
      'select' => ['minimumInputLength' => 0],
      'placeholder' => ts('Select Invoice Template'),
    ], TRUE);

    $this->add('text', 'invoice_prefix', 'Invoice Prefix', ['maxlength' => 11]);

    $this->add('text', 'next_invoice_number', 'Next Invoice Number', ['maxlength' => 11], TRUE);

    $this->addEntityRef('creditnote_template_id', ts('Credit Note Template'), [
      'entity' => 'MessageTemplate',
      'api' => [
        'search_field' => 'msg_title',
        'label_field' => 'msg_title',
        'params' => [
          'is_default' => 1,
        ],
      ],
      'select' => ['minimumInputLength' => 0],
      'placeholder' => ts('Select Credit Note Template'),
    ], TRUE);

    $this->add('text', 'creditnote_prefix', 'Credit Note Prefix', ['maxlength' => 11]);

    $this->add('text', 'next_creditnote_number', 'Next Credit Note Number', ['maxlength' => 11], TRUE);

    $this->addEntityRef(
      'receivable_payment_method',
      ts('Accounts Receivable Payment Method'),
      [
        'entity' => 'option_value',
        'api' => ['params' => ['option_group_id' => 'payment_instrument']],
        'select' => ['minimumInputLength' => 0],
        'placeholder' => ts('Select Payment Method'),
        'multiple' => FALSE,
      ],
      TRUE
    );

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);
  }

  public function setDefaultValues() {
    if (empty($this->id)) {
      return [];
    }

    $company = CRM_Financeextras_BAO_Company::getById($this->id);

    $values = [];
    $values['contact_id'] = $company->contact_id;
    $values['invoice_template_id'] = $company->invoice_template_id;
    $values['invoice_prefix'] = $company->invoice_prefix;
    $values['next_invoice_number'] = $company->next_invoice_number;
    $values['creditnote_template_id'] = $company->creditnote_template_id;
    $values['creditnote_prefix'] = $company->creditnote_prefix;
    $values['next_creditnote_number'] = $company->next_creditnote_number;
    $values['receivable_payment_method'] = $company->receivable_payment_method;

    return $values;
  }

  public function addRules() {
    $this->addFormRule(array('CRM_Financeextras_Form_Company_Add', 'validateNumericFields'));
  }

  public static function validateNumericFields($fields) {
    $errors = [];

    $fieldsToCheck = [
      ['field_name' => 'next_invoice_number', 'field_label' => 'Next invoice number'],
      ['field_name' => 'next_creditnote_number', 'field_label' => 'Next credit Note number'],
    ];
    foreach ($fieldsToCheck as $field) {
      $fieldName = $field['field_name'];
      $isNumericField = $fields[$fieldName] > 0 && (ctype_digit($fields[$fieldName]));
      if (!$isNumericField) {
        $errorMessage = $field['field_label'] . ' only accepts positive integers, with or without leading zeros.';
        $errors[$field['field_name']] = ts($errorMessage);
      }
    }

    if (count($errors) >= 1) {
      return $errors;
    }

    return TRUE;
  }

  public function postProcess() {
    $submittedValues = $this->exportValues();

    $notificationMessage = ts('New Company has been added successfully');
    if (!empty($this->id)) {
      $params['id'] = $this->id;
      $notificationMessage = ts('The company record has been updated.');
    }

    $params['contact_id'] = $submittedValues['contact_id'];
    $params['invoice_template_id'] = $submittedValues['invoice_template_id'];
    $params['invoice_prefix'] = $submittedValues['invoice_prefix'];
    $params['next_invoice_number'] = $submittedValues['next_invoice_number'];
    $params['creditnote_template_id'] = $submittedValues['creditnote_template_id'];
    $params['creditnote_prefix'] = $submittedValues['creditnote_prefix'];
    $params['next_creditnote_number'] = $submittedValues['next_creditnote_number'];
    $params['receivable_payment_method'] = $submittedValues['receivable_payment_method'];

    CRM_Financeextras_BAO_Company::create($params);

    CRM_Core_Session::setStatus($notificationMessage, ts('Saved'), 'success');
    $returnURL = CRM_Utils_System::url('civicrm/admin/financeextras/company', 'reset=1');
    CRM_Utils_System::redirect($returnURL);
  }

}
