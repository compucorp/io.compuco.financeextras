<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

use PHPUnit\Framework\TestCase;
use Exception;

/**
 * Test memory optimization features in InvoiceTemplate class.
 *
 * @group memory
 * @group financeextras
 */
class InvoiceTemplateMemoryTest extends TestCase {

  /**
   * @var array
   */
  private $originalLogLevel;

  public function setUp(): void {
    parent::setUp();

    // Reset static counters before each test
    $reflection = new \ReflectionClass(InvoiceTemplate::class);
    $property = $reflection->getProperty('processedInvoices');
    $property->setAccessible(TRUE);
    $property->setValue(NULL, 0);

    // Mock Civi log to capture logging calls
    if (!class_exists('\Civi')) {
      $this->createCiviMock();
    }
  }

  /**
   * Test that processed invoices counter increments correctly.
   */
  public function testProcessedInvoicesCounterIncrement(): void {
    $templateParams = [
      'tplParams' => ['id' => 123]
    ];

    // Mock ContributionOwnerOrganisation to return empty (early return)
    if (!class_exists('ContributionOwnerOrganisation')) {
      $this->mockContributionOwnerOrganisation();
    }

    $invoice1 = new InvoiceTemplate($templateParams, 'test');
    $invoice2 = new InvoiceTemplate($templateParams, 'test');

    // Get initial counter value
    $reflection = new \ReflectionClass(InvoiceTemplate::class);
    $property = $reflection->getProperty('processedInvoices');
    $property->setAccessible(TRUE);
    $initialCount = $property->getValue();

    // Process invoices
    $invoice1->handle();
    $this->assertEquals($initialCount + 1, $property->getValue());

    $invoice2->handle();
    $this->assertEquals($initialCount + 2, $property->getValue());
  }

  /**
   * Test garbage collection is triggered at correct intervals.
   */
  public function testGarbageCollectionTriggering(): void {
    $templateParams = [
      'tplParams' => ['id' => 123]
    ];

    if (!class_exists('ContributionOwnerOrganisation')) {
      $this->mockContributionOwnerOrganisation();
    }

    // Mock gc_collect_cycles to track calls
    $gcCalled = FALSE;
    if (!function_exists('gc_collect_cycles')) {
      function gc_collect_cycles() {
        global $gcCalled;
        $gcCalled = TRUE;
        return 0;
      };
    }

    // Process exactly 25 invoices to trigger GC
    for ($i = 1; $i <= 25; $i++) {
      $invoice = new InvoiceTemplate($templateParams, 'test');
      $invoice->handle();
    }

    // GC should have been called on the 25th invoice
    $this->assertTrue($gcCalled, 'Garbage collection should be triggered after 25 processed invoices');
  }

  /**
   * Test error handling and logging functionality.
   */
  public function testErrorHandlingAndLogging(): void {
    $templateParams = [
      'tplParams' => ['id' => 123]
    ];

    // Create a mock that throws an exception
    $invoice = $this->getMockBuilder(InvoiceTemplate::class)
      ->setConstructorArgs([$templateParams, 'test'])
      ->onlyMethods(['addTaxConversionTable'])
      ->getMock();

    $invoice->expects($this->once())
      ->method('addTaxConversionTable')
      ->willThrowException(new Exception('Test exception'));

    // Expect the exception to be re-thrown after logging
    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Test exception');

    $invoice->handle();
  }

  /**
   * Test memory usage remains reasonable during bulk processing.
   */
  public function testBulkProcessingMemoryUsage(): void {
    $templateParams = [
      'tplParams' => ['id' => 123]
    ];

    if (!class_exists('ContributionOwnerOrganisation')) {
      $this->mockContributionOwnerOrganisation();
    }

    $startMemory = memory_get_usage(TRUE);

    // Process 50 invoices (2 GC cycles)
    for ($i = 1; $i <= 50; $i++) {
      $invoice = new InvoiceTemplate($templateParams, 'test');
      $invoice->handle();
    }

    $endMemory = memory_get_usage(TRUE);
    $memoryIncrease = ($endMemory - $startMemory) / (1024 * 1024); // MB

    // Memory increase should be reasonable (under 10MB for 50 invoices)
    $this->assertLessThan(10, $memoryIncrease,
      'Memory usage should remain bounded during bulk processing');
  }

  /**
   * Test that static counter persists across multiple instances.
   */
  public function testStaticCounterPersistence(): void {
    $templateParams = [
      'tplParams' => ['id' => 123]
    ];

    if (!class_exists('ContributionOwnerOrganisation')) {
      $this->mockContributionOwnerOrganisation();
    }

    // Create and process multiple instances
    $instances = [];
    for ($i = 0; $i < 5; $i++) {
      $instances[] = new InvoiceTemplate($templateParams, 'test');
    }

    foreach ($instances as $index => $instance) {
      $instance->handle();

      // Check counter after each processing
      $reflection = new \ReflectionClass(InvoiceTemplate::class);
      $property = $reflection->getProperty('processedInvoices');
      $property->setAccessible(TRUE);

      $this->assertEquals($index + 1, $property->getValue(),
        'Static counter should persist across instances');
    }
  }

  /**
   * Mock ContributionOwnerOrganisation for testing.
   */
  private function mockContributionOwnerOrganisation(): void {
    if (!class_exists('ContributionOwnerOrganisation')) {
      eval('
        class ContributionOwnerOrganisation {
          public static function getOwnerOrganisationCompany($contributionId) {
            return NULL; // Return empty to trigger early return
          }
        }
      ');
    }
  }

  /**
   * Test LRU cache functionality for contribution data.
   */
  public function testContributionLRUCache(): void {
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
  public function testLRUCacheEviction(): void {
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
  public function testOwnerCompanyCache(): void {
    $templateParams = ['tplParams' => ['id' => 456]];
    $invoice = new InvoiceTemplate($templateParams, 'test');
    $reflection = new \ReflectionClass($invoice);

    $getOwnerCompanyFromCacheMethod = $reflection->getMethod('getOwnerCompanyFromCache');
    $getOwnerCompanyFromCacheMethod->setAccessible(TRUE);

    // Test cache miss
    $result = $getOwnerCompanyFromCacheMethod->invoke($invoice, 456);
    $this->assertFalse($result);

    // Add to cache
    $addToLRUCacheMethod = $reflection->getMethod('addToLRUCache');
    $addToLRUCacheMethod->setAccessible(TRUE);

    $cache = [];
    $order = [];
    $ownerData = ['contact_id' => 789, 'name' => 'Test Company'];

    $addToLRUCacheMethod->invoke($invoice, $cache, $order, 456, $ownerData);

    // Set static properties
    $ownerCacheProperty = $reflection->getProperty('ownerCompanyCache');
    $ownerCacheProperty->setAccessible(TRUE);
    $ownerCacheProperty->setValue(NULL, $cache);

    $ownerOrderProperty = $reflection->getProperty('ownerCompanyCacheOrder');
    $ownerOrderProperty->setAccessible(TRUE);
    $ownerOrderProperty->setValue(NULL, $order);

    // Test cache hit
    $result = $getOwnerCompanyFromCacheMethod->invoke($invoice, 456);
    $this->assertEquals($ownerData, $result);
  }

  /**
   * Test location cache functionality.
   */
  public function testLocationCache(): void {
    $templateParams = ['tplParams' => ['id' => 789]];
    $invoice = new InvoiceTemplate($templateParams, 'test');
    $reflection = new \ReflectionClass($invoice);

    $getLocationFromCacheMethod = $reflection->getMethod('getLocationFromCache');
    $getLocationFromCacheMethod->setAccessible(TRUE);

    // Test cache miss
    $result = $getLocationFromCacheMethod->invoke($invoice, 999);
    $this->assertFalse($result);
  }

  /**
   * Test LRU order management.
   */
  public function testLRUOrderManagement(): void {
    $templateParams = ['tplParams' => ['id' => 1]];
    $invoice = new InvoiceTemplate($templateParams, 'test');
    $reflection = new \ReflectionClass($invoice);

    $updateLRUOrderMethod = $reflection->getMethod('updateLRUOrder');
    $updateLRUOrderMethod->setAccessible(TRUE);

    $order = [1, 2, 3, 4];

    // Move item 2 to end (most recently used)
    $updateLRUOrderMethod->invoke($invoice, $order, 2);
    $this->assertEquals([0 => 1, 1 => 3, 2 => 4, 3 => 2], $order);

    // Move non-existent item
    $updateLRUOrderMethod->invoke($invoice, $order, 99);
    $this->assertEquals([0 => 1, 1 => 3, 2 => 4, 3 => 2, 4 => 99], $order);
  }

  /**
   * Create Civi mock for logging.
   */
  private function createCiviMock(): void {
    if (!class_exists('\Civi')) {
      eval('
        class Civi {
          public static function log() {
            return new class {
              public function error($message) {
                // Mock logger - could be enhanced to track calls
              }
            };
          }
        }
      ');
    }
  }

}
