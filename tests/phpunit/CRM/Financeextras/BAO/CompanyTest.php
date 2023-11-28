<?php

use CRM_Financeextras_BAO_Company as Company;

/**
 * @group headless
 */
class CRM_Financeextras_BAO_CompanyTest extends BaseHeadlessTest {

  public function testCreate() {
    $params = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => '000001',
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => '000002',
    ];

    $company = Company::create($params);

    foreach ($params as $paramKey => $paramValue) {
      $this->assertEquals($paramValue, $company->{$paramKey});
    }
  }

  public function testGetById() {
    $params = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => '000001',
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => '000002',
    ];

    $company = Company::create($params);

    $fetchedCompany = Company::getById($company->id);
    foreach ($params as $paramKey => $paramValue) {
      $this->assertEquals($params[$paramKey], $fetchedCompany->{$paramKey});
    }
  }

  public function testDeleteById() {
    $params = [
      'contact_id' => 1,
      'invoice_template_id' => 1,
      'invoice_prefix' => 'INV_',
      'next_invoice_number' => '000001',
      'creditnote_prefix' => 'CN_',
      'next_creditnote_number' => '000002',
    ];

    $company = Company::create($params);
    $this->assertTrue(!empty($company->id));

    Company::deleteById($company->id);

    $deletedCompany = Company::getById($company->id);
    $this->assertEquals(0, $deletedCompany->N);
  }

}
