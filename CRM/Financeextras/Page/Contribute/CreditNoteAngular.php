<?php

/**
 * Class CRM_Financeextras_Page_Contribute_CreditNoteAngular.
 *
 * Define an Angular base-page for Crediitnotes Module.
 */
class CRM_Financeextras_Page_Contribute_CreditNoteAngular extends \CRM_Core_Page {

  /**
   * {@inheritDoc}
   */
  public function run() {
    $route = $this->getRoute();
    $loader = Civi::service('angularjs.loader');
    $contributionId = CRM_Utils_Request::retrieve('contribution_id', 'Positive', $this, FALSE, 'null');
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 'null');
    $this->assign('contribution_id', $contributionId);
    $this->assign('contact_id', $contactId);
    $loader->addModules(['crmApp', 'fe-creditnote']);

    CRM_Utils_System::setTitle(ts($route['title']));

    return parent::run();
  }

  private function getRoute() {
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $routes = [CRM_Core_Action::ADD => ['name' => 'new', 'title' => 'New Credit Note']];
    return $routes[$action] ?? 'new';
  }

}
