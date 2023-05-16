<?php

use CRM_Financeextras_ExtensionUtil as E;
use CRM_Financeextras_Hook_PageRun_PageRunInterface as PageRunInterface;

class CRM_Financeextras_Hook_PageRun_ContributionPageTab implements PageRunInterface {

  /**
   * Handles the hook invocation.
   *
   * @param CRM_Core_Page $page
   */
  public function handle($page) {
    $this->addResources();
  }

  /**
   * Checks if the hook should run.
   *
   * @param CRM_Core_Page $page
   *
   * @return bool
   */
  public static function shouldHandle($page) {
    return $page instanceof CRM_Contribute_Page_Tab && $page->_action == CRM_Core_Action::BROWSE;
  }

  /**
   * Adds page resources.
   *
   * @param $region
   */
  private function addResources() {
    Civi::resources()->add([
      'template' => 'CRM/Financeextras/Page/Contribute/CreditNoteTab.tpl',
      'region' => 'page-header',
    ]);
    Civi::resources()->add([
      'scriptFile' => [E::LONG_NAME, 'js/creditnote.js'],
      'region' => 'page-header',
    ]);
  }

}
