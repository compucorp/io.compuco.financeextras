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
    $this->setCreditNoteCounts($page);
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

  /**
   * Sets the credit note counts for the current contact.
   *
   * @param CRM_Core_Page $page
   */
  private function setCreditNoteCounts($page) {
    $contactId = $page->getVar('_contactId');
    $creditNotes = \Civi\Api4\CreditNote::get(FALSE)
      ->selectRowCount()
      ->addSelect('COUNT(id) AS count')
      ->addWhere('contact_id', '=', $contactId)
      ->execute();

    $page->assign('creditNoteCount', $creditNotes[0]['count'] ?? 0);

  }

}
