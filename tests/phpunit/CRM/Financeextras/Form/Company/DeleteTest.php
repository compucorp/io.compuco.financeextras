<?php

/**
 * @group headless
 */
class CRM_Financeextras_Form_Company_DeleteTest extends BaseHeadlessTest {

  public function testCompanyDeletion() {
    $params = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => '000001',
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => '000002',
    ];
    $company = CRM_Financeextras_BAO_Company::create($params);

    $_REQUEST['id'] = $company->id;
    $this->submitForm([]);
    unset($_REQUEST['id']);

    $company = new CRM_Financeextras_DAO_Company();
    $company->contact_id = 1;
    $company->invoice_prefix = 'INV_';
    $company->next_invoice_number = '000001';
    $company->find();
    $records = $company->fetchAll();

    $this->assertCount(0, $records);
  }

  private function submitForm($formValues) {
    $form = new CRM_Financeextras_Form_Company_Delete();
    $form->controller = new CRM_Core_Controller_Simple('CRM_Financeextras_Form_Company_Delete', '');
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
