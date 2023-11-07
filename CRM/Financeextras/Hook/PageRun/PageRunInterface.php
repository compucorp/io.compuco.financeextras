<?php

interface CRM_Financeextras_Hook_PageRun_PageRunInterface {

  /**
   * @param CRM_Core_Page $page
   */
  public static function shouldHandle($page);

  /**
   * @param CRM_Core_Page $page
   */
  public function handle($page);

}
