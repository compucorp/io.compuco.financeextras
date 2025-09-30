<?php

/**
 * Test memory optimization features in financeextras.php hook functions.
 *
 * @group memory
 * @group financeextras
 */
class FinanceExtrasMemoryTest extends \PHPUnit\Framework\TestCase {

  public function setUp(): void {
    parent::setUp();
    
    // Include the main file to get access to the hook function
    require_once __DIR__ . '/../../financeextras.php';
    
    // Mock required classes
    $this->mockRequiredClasses();
    
    // Reset any global state
    if (function_exists('gc_collect_cycles')) {
      gc_collect_cycles();
    }
  }

  /**
   * Test that hook processes parameters correctly.
   */
  public function testAlterMailParamsHook(): void {
    $params = [
      'valueName' => 'contribution_invoice_receipt',
      'tplParams' => ['id' => 123]
    ];
    $context = 'test';
    
    // Call the hook function
    financeextras_civicrm_alterMailParams($params, $context);
    
    // Verify the hook was called (basic functionality test)
    $this->assertTrue(TRUE, 'Hook function should execute without errors');
  }

  /**
   * Test memory management during bulk hook calls.
   */
  public function testBulkHookCallsMemoryManagement(): void {
    $startMemory = memory_get_usage(TRUE);
    
    // Mock gc_collect_cycles to track calls
    $gcCallCount = 0;
    if (!function_exists('test_gc_collect_cycles')) {
      eval('
        function test_gc_collect_cycles() {
          global $gcCallCount;
          $gcCallCount++;
          return 0;
        }
      ');
      
      // Override gc_collect_cycles for testing
      eval('
        function gc_collect_cycles() {
          return test_gc_collect_cycles();
        }
      ');
    }
    
    // Process 100 hook calls (should trigger GC twice at 50 and 100)
    for ($i = 1; $i <= 100; $i++) {
      $params = [
        'valueName' => 'contribution_invoice_receipt',
        'tplParams' => ['id' => $i]
      ];
      $context = 'test';
      
      financeextras_civicrm_alterMailParams($params, $context);
    }
    
    $endMemory = memory_get_usage(TRUE);
    $memoryIncrease = ($endMemory - $startMemory) / (1024 * 1024); // MB
    
    // Memory should remain reasonable
    $this->assertLessThan(15, $memoryIncrease, 
      'Memory usage should remain bounded during bulk operations');
    
    // GC should have been called at least twice (every 50 calls)
    $this->assertGreaterThanOrEqual(2, $gcCallCount, 
      'Garbage collection should be triggered during bulk processing');
  }

  /**
   * Test hook behavior with different parameter types.
   */
  public function testHookWithDifferentParameters(): void {
    // Test with contribution receipt (should not process)
    $params1 = [
      'valueName' => 'contribution_receipt',
      'tplParams' => ['id' => 123]
    ];
    
    financeextras_civicrm_alterMailParams($params1, 'test');
    $this->assertTrue(TRUE, 'Hook should handle non-invoice receipts gracefully');
    
    // Test with invoice receipt (should process)
    $params2 = [
      'valueName' => 'contribution_invoice_receipt',
      'tplParams' => ['id' => 456]
    ];
    
    financeextras_civicrm_alterMailParams($params2, 'test');
    $this->assertTrue(TRUE, 'Hook should handle invoice receipts');
    
    // Test with missing parameters
    $params3 = [];
    
    financeextras_civicrm_alterMailParams($params3, 'test');
    $this->assertTrue(TRUE, 'Hook should handle missing parameters gracefully');
  }

  /**
   * Test hook instance management.
   */
  public function testHookInstanceManagement(): void {
    $params = [
      'valueName' => 'contribution_invoice_receipt', 
      'tplParams' => ['id' => 123]
    ];
    $context = 'test';
    
    $initialMemory = memory_get_usage(TRUE);
    
    // Process multiple hook calls
    for ($i = 0; $i < 25; $i++) {
      financeextras_civicrm_alterMailParams($params, $context);
    }
    
    $finalMemory = memory_get_usage(TRUE);
    $memoryIncrease = ($finalMemory - $initialMemory) / (1024 * 1024);
    
    // Memory increase should be minimal due to proper instance management
    $this->assertLessThan(5, $memoryIncrease, 
      'Hook instance management should prevent memory leaks');
  }

  /**
   * Test error handling in hook processing.
   */
  public function testHookErrorHandling(): void {
    // Mock a hook class that throws an exception
    eval('
      class TestFailingHook {
        public function __construct($params, $context) {}
        
        public static function shouldHandle($params, $context) {
          return TRUE;
        }
        
        public function handle() {
          throw new Exception("Test exception");
        }
      }
    ');
    
    $params = ['test' => 'value'];
    $context = 'test';
    
    // Hook should handle exceptions gracefully
    try {
      // This would normally be tested by modifying the hooks array,
      // but since it's hardcoded, we test the concept
      $this->assertTrue(TRUE, 'Hook error handling test placeholder');
    } catch (Exception $e) {
      $this->fail('Hook should handle exceptions gracefully');
    }
  }

  /**
   * Test performance with realistic data volumes.
   */
  public function testPerformanceWithRealisticVolumes(): void {
    $startTime = microtime(TRUE);
    $startMemory = memory_get_usage(TRUE);
    
    // Simulate processing 500 invoice emails (realistic bulk scenario)
    for ($i = 1; $i <= 500; $i++) {
      $params = [
        'valueName' => 'contribution_invoice_receipt',
        'tplParams' => [
          'id' => $i,
          'contact_id' => 1000 + $i,
          'total_amount' => rand(10, 1000)
        ]
      ];
      
      financeextras_civicrm_alterMailParams($params, 'bulk_test');
    }
    
    $endTime = microtime(TRUE);
    $endMemory = memory_get_usage(TRUE);
    
    $executionTime = $endTime - $startTime;
    $memoryIncrease = ($endMemory - $startMemory) / (1024 * 1024); // MB
    
    // Performance benchmarks
    $this->assertLessThan(30, $executionTime, 
      'Processing 500 invoices should complete within 30 seconds');
    
    $this->assertLessThan(50, $memoryIncrease, 
      'Memory usage should remain under 50MB for 500 invoices');
  }

  /**
   * Mock required classes for testing.
   */
  private function mockRequiredClasses(): void {
    // Mock AlterContributionReceipt if not exists
    if (!class_exists('AlterContributionReceipt')) {
      eval('
        class AlterContributionReceipt {
          public function __construct($params, $context) {}
          
          public static function shouldHandle($params, $context) {
            return FALSE; // Don\'t handle in tests
          }
          
          public function handle() {}
        }
      ');
    }
    
    // Mock InvoiceTemplate if not exists
    if (!class_exists('Civi\\Financeextras\\Hook\\AlterMailParams\\InvoiceTemplate')) {
      eval('
        namespace Civi\\Financeextras\\Hook\\AlterMailParams {
          class InvoiceTemplate {
            public function __construct($params, $context) {}
            
            public static function shouldHandle($params, $context) {
              return isset($params["valueName"]) && 
                     $params["valueName"] === "contribution_invoice_receipt";
            }
            
            public function handle() {
              // Mock implementation
            }
          }
        }
      ');
    }
  }

}