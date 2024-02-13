<?php

namespace Civi\Financeextras\Hook\AlterContent;

class BatchTransaction {

  private $content;

  public function __construct(&$content) {
    $this->content = &$content;
  }

  public function run() {
    $this->enforceSearchFiltersInBatchScreen();
    $this->useCustomFinancialTransactionList();
  }

  /**
   * When loading the batch transactions screen
   * CiviCRM ignores the filters to prevent running
   * more complex query that is often not needed, and
   * assumes the user can do further filtration manually if
   * needed from the same screen.
   *
   * But given We've added the owner organisation field
   * as a filter and that we must enforce it even without
   * the user manual intervention, we update the page template
   * here to make sure the filter are always enforce.
   *
   * @return void
   */
  private function enforceSearchFiltersInBatchScreen() {
    $this->content = str_replace('buildTransactionSelectorAssign( false )', 'buildTransactionSelectorAssign( true )', $this->content);
  }

  /**
   * Updates the template to use our custom transctionlist fetch method.
   *
   * This method includes creditnote transactions in the batch trnsaction list table,
   * and also hides the financial type column ('crm-type').
   */
  private function useCustomFinancialTransactionList() {
    $this->content = str_replace('CRM_Financial_Page_AJAX&fnName=getFinancialTransactionsList', 'CRM_Financeextras_Page_AJAX&fnName=getFinancialTransactionsList', $this->content);
    $this->content = str_replace("{sClass:'crm-type'}", "{sClass:'crm-type', 'bVisible': false}", $this->content);
  }

}
