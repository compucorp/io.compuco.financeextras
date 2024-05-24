<?php

/**
 * @group headless
 */
class CRM_Financeextras_Page_CompanyTest extends BaseHeadlessTest {

  public function setUp() {
  }

  public function testCompanyRecordsAppearInPage() {
    $params1 = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV',
      'next_invoice_number' => '000001',
      'creditnote_prefix' => 'CN',
      'next_creditnote_number' => '000002',
      'receivable_payment_method' => 1,
    ];
    CRM_Financeextras_BAO_Company::create($params1);

    $params2 = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'TRX',
      'next_invoice_number' => '000005',
      'creditnote_prefix' => 'XH',
      'next_creditnote_number' => '000006',
      'receivable_payment_method' => 2,
    ];
    CRM_Financeextras_BAO_Company::create($params2);

    $page = new CRM_Financeextras_Page_Company();
    $this->disableReturningPageResult($page);
    $page->run();

    $rowsToShowInPage = $page->get_template_vars('rows');
    ksort($rowsToShowInPage);
    // we look for 3 records because there is a one created
    // by default when installing the extension.
    $this->assertCount(3, $rowsToShowInPage);
    $secondRowAfterDefaultRecord = next($rowsToShowInPage);
    $this->assertEquals('Default Organization', $secondRowAfterDefaultRecord['company_name']);
    $this->assertEquals($params1['next_invoice_number'], $secondRowAfterDefaultRecord['next_invoice_number']);

    $thirdRow = next($rowsToShowInPage);
    $this->assertEquals('Default Organization', $thirdRow['company_name']);
    $this->assertEquals($params2['next_invoice_number'], $thirdRow['next_invoice_number']);
  }

  private function disableReturningPageResult($page) {
    $refObject   = new ReflectionObject($page);
    $refProperty = $refObject->getProperty('_embedded');
    $refProperty->setAccessible(TRUE);
    $refProperty->setValue($page, TRUE);
  }

}
