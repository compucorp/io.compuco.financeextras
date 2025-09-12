<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Financeextras_BAO_BatchOwnerOrganisation as BatchOwnerOrganisation;
use CRM_Financeextras_ExtensionUtil as ExtensionUtil;

class FinancialBatch {

  private $form;

  public function __construct($form) {
    $this->form = $form;
  }

  public function handle() {
    $this->addOwnerOrganisationField();
  }

  private function addOwnerOrganisationField() {
    // adds the owner org field element to the QuickForm instance
    $this->form->addEntityRef('financeextras_owner_org_id', ts('Owner Organisation(s)'), [
      'api' => ['params' => ['contact_type' => 'Organization']],
      'select' => ['minimumInputLength' => 0],
      'placeholder' => ts('Select Owner Organisation(s)'),
      'multiple' => TRUE,
    ], FALSE);

    $this->setDefaultValueOnUpdateForm();

    // adds the HTML markup to the form
    $templatePath = ExtensionUtil::path() . '/templates';
    \CRM_Core_Region::instance('page-body')->add([
      'template' => "{$templatePath}/CRM/Financeextras/Hook/BuildForm/FinancialBatch.tpl",
    ]);

    \Civi::resources()->addScriptFile('io.compuco.financeextras', 'js/financialbatch.js');
  }

  /**
   * Sets the owner organization field
   * default value in case it is the batch
   * update form.
   *
   * @return void
   */
  private function setDefaultValueOnUpdateForm() {
    if ($this->form->getAction() & \CRM_Core_Action::UPDATE) {
      $batchId = $this->form->_id;
      $batchOwnerOrganisationIds = BatchOwnerOrganisation::getByBatchId($batchId);
      $defaults['financeextras_owner_org_id'] = $batchOwnerOrganisationIds;
      $this->form->setDefaults($defaults);
    }
  }

  public static function shouldHandle($form, $formName) {
    $addOrUpdate = ($form->getAction() & \CRM_Core_Action::ADD) || ($form->getAction() & \CRM_Core_Action::UPDATE);
    if ($formName == 'CRM_Financial_Form_FinancialBatch' && $addOrUpdate) {
      return TRUE;
    }

    return FALSE;
  }

}
