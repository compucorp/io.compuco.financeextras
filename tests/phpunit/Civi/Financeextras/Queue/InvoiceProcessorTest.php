<?php

namespace Civi\Financeextras\Queue;

use BaseHeadlessTest;
use CRM_Queue_Service;

/**
 * Test queue-based invoice processing.
 * 
 * @group headless
 * @group queue
 */
class InvoiceProcessorTest extends BaseHeadlessTest {

  private $testContributions = [];

  public function setUp() {
    parent::setUp();
    
    // Create test contributions
    for ($i = 1; $i <= 5; $i++) {
      $contribution = civicrm_api3('Contribution', 'create', [
        'financial_type_id' => 'Donation',
        'receive_date' => '2022-11-11',
        'total_amount' => 100 + $i,
        'contact_id' => 1,
      ]);
      $this->testContributions[] = $contribution['id'];
    }
  }

  public function tearDown() {
    // Clean up test contributions
    foreach ($this->testContributions as $contributionId) {
      try {
        civicrm_api3('Contribution', 'delete', ['id' => $contributionId]);
      } catch (\Exception $e) {
        // Ignore cleanup errors
      }
    }
    
    parent::tearDown();
  }

  /**
   * Test basic queue creation.
   */
  public function testCreateInvoiceQueue() {
    $contributionIds = array_slice($this->testContributions, 0, 3);
    
    $queue = InvoiceProcessor::createInvoiceQueue($contributionIds);
    
    $this->assertNotNull($queue);
    $this->assertStringStartsWith(InvoiceProcessor::QUEUE_PREFIX, $queue->getName());
    $this->assertEquals(3, $queue->numberOfItems());
  }

  /**
   * Test queue creation with custom options.
   */
  public function testCreateInvoiceQueueWithCustomOptions() {
    $contributionIds = array_slice($this->testContributions, 0, 2);
    $customQueueName = 'test_invoice_queue_' . uniqid();
    
    $options = ['queue_name' => $customQueueName];
    
    $queue = InvoiceProcessor::createInvoiceQueue($contributionIds, $options);
    
    $this->assertEquals($customQueueName, $queue->getName());
    $this->assertEquals(2, $queue->numberOfItems());
  }

  /**
   * Test processing a single invoice.
   */
  public function testProcessSingleInvoice() {
    $contributionId = $this->testContributions[0];
    
    // Create a mock queue task context
    $mockContext = new \stdClass();
    
    $result = InvoiceProcessor::processInvoice($mockContext, $contributionId);
    
    $this->assertTrue($result, 'Invoice processing should succeed');
  }

  /**
   * Test processing with invalid contribution ID.
   */
  public function testProcessInvalidContribution() {
    $invalidId = 999999;
    
    // Create a mock queue task context
    $mockContext = new \stdClass();
    
    $result = InvoiceProcessor::processInvoice($mockContext, $invalidId);
    
    // Should return FALSE for invalid contribution but not throw exception
    $this->assertFalse($result, 'Processing invalid contribution should return FALSE');
  }

  /**
   * Test getting queue status.
   */
  public function testGetQueueStatus() {
    $contributionIds = array_slice($this->testContributions, 0, 3);
    $queue = InvoiceProcessor::createInvoiceQueue($contributionIds);
    
    $status = InvoiceProcessor::getQueueStatus($queue->getName());
    
    $this->assertArrayHasKey('queue_name', $status);
    $this->assertArrayHasKey('total_items', $status);
    $this->assertArrayHasKey('status', $status);
    $this->assertEquals($queue->getName(), $status['queue_name']);
    $this->assertEquals(3, $status['total_items']);
    $this->assertEquals('pending', $status['status']);
  }

  /**
   * Test getting status of non-existent queue.
   */
  public function testGetQueueStatusNonExistent() {
    $status = InvoiceProcessor::getQueueStatus('non_existent_queue');
    
    $this->assertArrayHasKey('error', $status);
    $this->assertEquals('error', $status['status']);
  }

  /**
   * Test complete queue processing workflow.
   */
  public function testCompleteQueueWorkflow() {
    $contributionIds = array_slice($this->testContributions, 0, 2);
    
    // Create and run queue
    $result = InvoiceProcessor::processInvoicesViaQueue($contributionIds);
    
    $this->assertArrayHasKey('queue', $result);
    $this->assertArrayHasKey('runner', $result);
    $this->assertArrayHasKey('queue_name', $result);
    $this->assertArrayHasKey('item_count', $result);
    
    $this->assertEquals(2, $result['item_count']);
    $this->assertStringStartsWith(InvoiceProcessor::QUEUE_PREFIX, $result['queue_name']);
  }

  /**
   * Test error handling in queue processing.
   */
  public function testQueueErrorHandling() {
    // Include an invalid contribution ID to test error handling
    $contributionIds = [$this->testContributions[0], 999999, $this->testContributions[1]];
    
    $result = InvoiceProcessor::processInvoicesViaQueue($contributionIds);
    
    // Should still create queue and process valid items
    $this->assertArrayHasKey('queue_name', $result);
    $this->assertEquals(3, $result['item_count']);
  }

}