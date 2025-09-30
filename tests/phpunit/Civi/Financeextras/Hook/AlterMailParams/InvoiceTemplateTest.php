<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

use BaseHeadlessTest;

/**
 * @group headless
 */
class InvoiceTemplateTest extends BaseHeadlessTest {

  private $company;

  public function setUp() {
    parent::setUp();

    $this->company = $this->createCompany(1);
    $this->updateFinancialAccountOwner('Donation', $this->company['contact_id']);
  }

  public function testStandardInvoiceTemplateWillBeReplacedByContributionOwnerOrganisationTemplate() {
    $contribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    \CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation::setOwnerOrganisation($contribution['id'], $this->company['contact_id']);

    $templateParams['messageTemplateID'] = NULL;
    $templateParams['isTest'] = TRUE;
    $templateParams['tplParams']['id'] = $contribution['id'];
    $alterInvoiceParams = new \Civi\Financeextras\Hook\AlterMailParams\InvoiceTemplate($templateParams, '');
    $alterInvoiceParams->handle();

    $this->assertEquals($this->company['invoice_template_id'], $templateParams['messageTemplateID']);
  }

  public function testDomainTokensWillBeReplacedByOwnerOrganisationDetails() {
    // set owner organisation address
    $addressParams = [
      'contact_id' => $this->company['contact_id'],
      'location_type_id' => 'Billing',
      'is_primary' => 1,
      'street_address' => 'teststreet',
      'supplemental_address_1' => 'testsupp1',
      'supplemental_address_2' => 'testsupp2',
      'supplemental_address_3' => 'testsupp3',
      'city' => 'testcity',
      'postal_code' => '0056',
      'country_id' => 'GB',
      'state_province_id' => 'Aberdeen City',
    ];
    civicrm_api3('Address', 'create', $addressParams);

    // set owner organisation email
    civicrm_api3('Email', 'create', [
      'contact_id' => $this->company['contact_id'],
      'email' => 'testorg1@example.com',
    ]);

    // set owner organisation phone
    civicrm_api3('Phone', 'create', [
      'contact_id' => $this->company['contact_id'],
      'phone' => '079000005',
    ]);

    $contribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    \CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation::setOwnerOrganisation($contribution['id'], $this->company['contact_id']);

    $templateParams['isTest'] = TRUE;
    $templateParams['tplParams'] = NULL;
    $templateParams['tplParams']['id'] = $contribution['id'];
    $alterInvoiceParams = new \Civi\Financeextras\Hook\AlterMailParams\InvoiceTemplate($templateParams, '');
    $alterInvoiceParams->handle();
    unset($templateParams['tplParams']['id']);
    //Remove other keys added by the hook
    $remove = ['showTaxConversionTable', 'rate_1_unit_tax_currency',
      'rate_1_unit_contribution_currency', 'sales_tax_currency', 'rate_vat_text',
    ];
    $templateParams['tplParams'] = array_diff_key($templateParams['tplParams'], array_flip($remove));

    $expectedParams = [
      'domain_organization' => 'testorg1',
      'domain_logo' => '',
      'domain_street_address' => 'teststreet',
      'domain_supplemental_address_1' => 'testsupp1',
      'domain_supplemental_address_2' => 'testsupp2',
      'domain_supplemental_address_3' => 'testsupp3',
      'domain_city' => 'testcity',
      'domain_postal_code' => '0056',
      'domain_state' => 'ABE',
      'domain_country' => 'United Kingdom',
      'domain_email' => 'testorg1@example.com',
      'domain_phone' => '079000005',
    ];
    $this->assertEquals($expectedParams, $templateParams['tplParams']);
  }

  public function testDomainLogoTokenWillResolveToTheOrganisationImageURL() {
    // update owner organisation profile image
    $fakeOrganisationImageURL = 'https://example.com/test1/test2/image.png';
    civicrm_api3('Contact', 'create', [
      'sequential' => 1,
      'id' => $this->company['contact_id'],
      'image_URL' => $fakeOrganisationImageURL,
    ]);

    $contribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    \CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation::setOwnerOrganisation($contribution['id'], $this->company['contact_id']);

    $templateParams['isTest'] = TRUE;
    $templateParams['tplParams'] = NULL;
    $templateParams['tplParams']['id'] = $contribution['id'];
    $alterInvoiceParams = new \Civi\Financeextras\Hook\AlterMailParams\InvoiceTemplate($templateParams, '');
    $alterInvoiceParams->handle();

    $this->assertEquals($fakeOrganisationImageURL, $templateParams['tplParams']['domain_logo']);
  }
  
  /**
   * Test LRU cache functionality for contribution data.
   */
  public function testContributionLRUCache() {
    $templateParams = ['tplParams' => ['id' => 123]];
    $invoice = new InvoiceTemplate($templateParams, 'test');
    $reflection = new \ReflectionClass($invoice);
    
    // Get private methods
    $addToLRUCacheMethod = $reflection->getMethod('addToLRUCache');
    $addToLRUCacheMethod->setAccessible(TRUE);
    $getContributionFromCacheMethod = $reflection->getMethod('getContributionFromCache');
    $getContributionFromCacheMethod->setAccessible(TRUE);
    
    // Test cache miss
    $result = $getContributionFromCacheMethod->invoke($invoice, 999);
    $this->assertFalse($result);
    
    // Add to cache and test hit
    $cache = [];
    $order = [];
    $testData = ['id' => 123, 'rate_1_unit_tax_currency' => '1.2'];
    
    $addToLRUCacheMethod->invoke($invoice, $cache, $order, 123, $testData);
    
    // Set static cache properties
    $contributionCacheProperty = $reflection->getProperty('contributionCache');
    $contributionCacheProperty->setAccessible(TRUE);
    $contributionCacheProperty->setValue(NULL, $cache);
    
    $contributionOrderProperty = $reflection->getProperty('contributionCacheOrder');
    $contributionOrderProperty->setAccessible(TRUE);
    $contributionOrderProperty->setValue(NULL, $order);
    
    $result = $getContributionFromCacheMethod->invoke($invoice, 123);
    $this->assertEquals($testData, $result);
  }
  
  /**
   * Test LRU cache eviction when at capacity.
   */
  public function testLRUCacheEviction() {
    $templateParams = ['tplParams' => ['id' => 1]];
    $invoice = new InvoiceTemplate($templateParams, 'test');
    $reflection = new \ReflectionClass($invoice);
    
    $addToLRUCacheMethod = $reflection->getMethod('addToLRUCache');
    $addToLRUCacheMethod->setAccessible(TRUE);
    $maxCacheSizeProperty = $reflection->getProperty('maxCacheSize');
    $maxCacheSizeProperty->setAccessible(TRUE);
    
    // Set small cache size for testing
    $maxCacheSizeProperty->setValue(NULL, 2);
    
    $cache = [];
    $order = [];
    
    // Fill cache to capacity
    $addToLRUCacheMethod->invoke($invoice, $cache, $order, 1, 'data1');
    $addToLRUCacheMethod->invoke($invoice, $cache, $order, 2, 'data2');
    
    $this->assertCount(2, $cache);
    $this->assertTrue(isset($cache[1]));
    
    // Add one more - should evict LRU
    $addToLRUCacheMethod->invoke($invoice, $cache, $order, 3, 'data3');
    
    $this->assertCount(2, $cache);
    $this->assertFalse(isset($cache[1])); // First item evicted
    $this->assertTrue(isset($cache[3])); // New item present
  }
  
  /**
   * Test owner company cache functionality.
   */
  public function testOwnerCompanyCache() {
    $templateParams = ['tplParams' => ['id' => 456]];
    $invoice = new InvoiceTemplate($templateParams, 'test');
    $reflection = new \ReflectionClass($invoice);
    
    $getOwnerCompanyFromCacheMethod = $reflection->getMethod('getOwnerCompanyFromCache');
    $getOwnerCompanyFromCacheMethod->setAccessible(TRUE);
    
    // Test cache miss
    $result = $getOwnerCompanyFromCacheMethod->invoke($invoice, 456);
    $this->assertFalse($result);
  }

}
