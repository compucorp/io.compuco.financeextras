<?php

/**
 * @group headless
 */
class CRM_Financeextras_Form_Company_AddTest extends BaseHeadlessTest {

  public function testRequiredFieldsValidation() {
    $formValues = [
      'contact_id' => '',
      'invoice_template_id' => '',
      'invoice_prefix' => '',
      'next_invoice_number' => '',
      'creditnote_template_id' => '',
      'creditnote_prefix' => '',
      'next_creditnote_number' => '',
    ];
    $form = $this->submitForm($formValues);
    $actualErrors = $form->_errors;

    $expectedErrors = [
      'contact_id' => 'Organisation is a required field.',
      'invoice_template_id' => 'Invoice Template is a required field.',
      'next_invoice_number' => 'Next Invoice Number is a required field.',
      'creditnote_template_id' => 'Credit Note Template is a required field.',
      'next_creditnote_number' => 'Next Credit Note Number is a required field.',
    ];

    $this->assertEquals($expectedErrors, $actualErrors);
  }

  public function testNumericalFieldsValidation() {
    $formValues = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => 'YAYAYA',
      'creditnote_template_id' => 1,
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => 'YAYAYA',
    ];

    $form = $this->submitForm($formValues);
    $actualErrors = $form->_errors;

    $expectedErrors = [
      'next_invoice_number' => 'Next invoice number only accepts positive integers, with or without leading zeros.',
      'next_creditnote_number' => 'Next credit Note number only accepts positive integers, with or without leading zeros.',
    ];

    $this->assertEquals($expectedErrors, $actualErrors);
  }

  public function testAddingNewCompanySuccessfully() {
    $formValues = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => '00001',
      'creditnote_template_id' => 1,
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => '00002',
    ];

    $this->submitForm($formValues);

    $company = new CRM_Financeextras_DAO_Company();
    $company->contact_id = 1;
    $company->invoice_prefix = 'INV_';
    $company->next_invoice_number = '00001';
    $company->find();
    $records = $company->fetchAll();

    $this->assertEquals(1, count($records));
    foreach ($formValues as $key => $value) {
      $this->assertEquals($formValues[$key], $records[0][$key]);
    }
  }

  public function testUpdatingCompanySuccessfully() {
    $params = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => '000001',
      'creditnote_template_id' => 1,
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => '000002',
    ];
    $company = CRM_Financeextras_BAO_Company::create($params);

    $_REQUEST['id'] = $company->id;
    $params['invoice_prefix'] = 'XYZ';
    $params['next_invoice_number'] = '000025';
    $this->submitForm($params);
    unset($_REQUEST['id']);

    $company = new CRM_Financeextras_DAO_Company();
    $company->contact_id = 1;
    $company->invoice_prefix = 'XYZ';
    $company->next_invoice_number = '000025';
    $company->find();
    $records = $company->fetchAll();

    $this->assertEquals($params['invoice_prefix'], $records[0]['invoice_prefix']);
    $this->assertEquals($params['next_invoice_number'], $records[0]['next_invoice_number']);
  }

  private function submitForm($formValues) {
    $form = new CRM_Financeextras_Form_Company_Add();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Financeextras_Form_Company_Add', '');
    $form->_submitValues = $formValues;

    $form->buildForm();
    $form->loadValues($formValues);
    $form->validate();

    try {
      $form->postProcess();
    }
    catch (Exception $e) {
      // postProcess for this form does a redirect
      // that ends up calling CRM_Utils_System::civiExit(),
      // but this method throw an exception from unit test
      // context, so we are ignoring this exception here
    }

    return $form;
  }

}
