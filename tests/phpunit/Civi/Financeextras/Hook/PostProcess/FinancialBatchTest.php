<?php

namespace Civi\Financeextras\Hook\PostProcess;

use BaseHeadlessTest;

/**
 * @group headless
 */
class FinancialBatchTest extends BaseHeadlessTest {

  public function testOwnerOrganisationsAreNotRequiredToCreateABatch() {
    $formValues = [
      'title' => 'testbatch1',
      'financeextras_owner_org_id' => NULL,
      'item_count' => NULL,
      'total' => NULL,
    ];

    $form = $this->submitForm($formValues);
    $hook = new \Civi\Financeextras\Hook\PostProcess\FinancialBatchPostProcess($form);
    $hook->handle();

    $createdBatch = civicrm_api3('Batch', 'get', [
      'name' => 'testbatch1',
      'sequential' => 1,
    ]);

    $this->assertEquals(1, $createdBatch['count']);
  }

  public function testCreatingBatchWithOneOwnerOrganisation() {
    $firstOrg = $this->createOrganisation('testorg1');

    $formValues = [
      'title' => 'testbatch2',
      'financeextras_owner_org_id' => $firstOrg['id'],
      'item_count' => NULL,
      'total' => NULL,
    ];

    $form = $this->submitForm($formValues);
    $hook = new \Civi\Financeextras\Hook\PostProcess\FinancialBatchPostProcess($form);
    $hook->handle();

    $createdBatch = civicrm_api3('Batch', 'get', [
      'name' => 'testbatch2',
      'sequential' => 1,
    ]);
    $batchOwnerOrgs = \CRM_Financeextras_BAO_BatchOwnerOrganisation::getByBatchId($createdBatch['id']);

    $this->assertEquals([$firstOrg['id']], $batchOwnerOrgs);
  }

  public function testCreatingBatchWithTwoOwnerOrganisations() {
    $firstOrg = $this->createOrganisation('testorg1');
    $secondOrg = $this->createOrganisation('testorg2');

    $formValues = [
      'title' => 'testbatch3',
      'financeextras_owner_org_id' => implode(',', [$firstOrg['id'], $secondOrg['id']]),
      'item_count' => NULL,
      'total' => NULL,
    ];

    $form = $this->submitForm($formValues);
    $hook = new \Civi\Financeextras\Hook\PostProcess\FinancialBatchPostProcess($form);
    $hook->handle();

    $createdBatch = civicrm_api3('Batch', 'get', [
      'name' => 'testbatch3',
      'sequential' => 1,
    ]);
    $batchOwnerOrgs = \CRM_Financeextras_BAO_BatchOwnerOrganisation::getByBatchId($createdBatch['id']);

    $this->assertEquals([$firstOrg['id'], $secondOrg['id']], $batchOwnerOrgs);
  }

  private function createOrganisation($orgName) {
    return civicrm_api3('Contact', 'create', [
      'sequential' => 1,
      'contact_type' => 'Organization',
      'organization_name' => $orgName,
    ])['values'][0];
  }

  private function submitForm($formValues) {
    $form = new \CRM_Financial_Form_FinancialBatch();
    $form->controller = new \CRM_Core_Controller_Simple('CRM_Financial_Form_FinancialBatch', '');
    $form->_submitValues = $formValues;
    $form->_action = \CRM_Core_Action::ADD;

    $form->buildForm();
    $form->loadValues($formValues);
    $form->validate();
    try {
      $form->postProcess();
    }
    catch (\Exception $e) {
      // postProcess for this form does a redirect
      // that ends up calling CRM_Utils_System::civiExit(),
      // but this method throw an exception from unit test
      // context, so we are ignoring this exception here.
    }

    return $form;
  }

}
