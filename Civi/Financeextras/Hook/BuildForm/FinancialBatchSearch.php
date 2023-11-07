<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Financeextras_ExtensionUtil as ExtensionUtil;

class FinancialBatchSearch {

  private $form;

  public function __construct($form) {
    $this->form = $form;
  }

  public function handle() {
    $this->addOwnerOrganisationFilterField();
  }

  private function addOwnerOrganisationFilterField() {
    // adds the owner org field element to the QuickForm instance
    $this->form->addEntityRef('financeextras_owner_org_id', ts('Owner Organisation(s)'), [
      'api' => ['params' => ['contact_type' => 'Organization']],
      'select' => ['minimumInputLength' => 0],
      'placeholder' => ts('Select Owner Organisation(s)'),
      'multiple' => TRUE,
    ], FALSE);

    // adds the HTML markup to the form
    $templatePath = ExtensionUtil::path() . '/templates';
    \CRM_Core_Region::instance('page-body')->add([
      'template' => "{$templatePath}/CRM/Financeextras/Hook/BuildForm/FinancialBatchSearch.tpl",
    ]);
  }

  public static function shouldHandle($form, $formName) {
    return $formName === "CRM_Financial_Form_Search";
  }

}
