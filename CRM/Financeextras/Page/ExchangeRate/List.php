<?php

use CRM_Financeextras_ExtensionUtil as E;

/**
 * Class CRM_Financeextras_Page_ExchangeRate_List.
 */
class CRM_Financeextras_Page_ExchangeRate_List extends CRM_Core_Page {

  private $title = 'Exchange Rates';

  /**
   * {@inheritDoc}
   */
  public function run() {
    CRM_Utils_System::setTitle(E::ts($this->title));

    $this->assign('title', E::ts($this->title));
    $loader = Civi::service('angularjs.loader');
    $loader->addModules(['crmApp', 'fe-exchange-rate']);
    parent::run();
  }

}
