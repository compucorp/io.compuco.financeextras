<?php

class CRM_Financeextras_Page_Company extends CRM_Core_Page {

  public function run() {
    $this->browse();

    parent::run();
  }

  public function browse() {
    $getQuery = 'SELECT fc.*, cc.display_name as company_name, mt.msg_title as invoice_template_name, mt2.msg_title as creditnote_template_name FROM financeextras_company fc
                 LEFT JOIN civicrm_contact cc on cc.id = fc.contact_id
                 LEFT JOIN civicrm_msg_template mt ON mt.id = fc.invoice_template_id
                 LEFT JOIN civicrm_msg_template mt2 ON mt2.id = fc.creditnote_template_id
                 ';
    $company = CRM_Core_DAO::executeQuery($getQuery);
    $rows = [];
    while ($company->fetch()) {
      $rows[$company->id] = $company->toArray();

      $rows[$company->id]['action'] = CRM_Core_Action::formLink(
        $this->generateActionLinks(),
        $this->calculateLinksMask(),
        ['id' => $company->id]
      );
    }

    $this->assign('rows', $rows);
  }

  private function generateActionLinks() {
    return [
      CRM_Core_Action::UPDATE  => [
        'name'  => ts('Edit'),
        'url'   => 'civicrm/admin/financeextras/company/add',
        'qs'    => 'id=%%id%%&reset=1',
      ],
      CRM_Core_Action::DELETE => [
        'name' => ts('Delete'),
        'url' => 'civicrm/admin/financeextras/company/delete',
        'qs' => 'id=%%id%%',
      ],
    ];
  }

  private function calculateLinksMask() {
    return array_sum(array_keys($this->generateActionLinks()));
  }

}
