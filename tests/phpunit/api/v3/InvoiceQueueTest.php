<?php

/**
 * Test InvoiceQueue API.
 * 
 * @group headless
 * @group api
 */
class api_v3_InvoiceQueueTest extends \PHPUnit\Framework\TestCase {

  private $testContributions = [];

  public function setUp(): void {
    parent::setUp();
    
    // Create test contributions
    for ($i = 1; $i <= 3; $i++) {
      $contribution = civicrm_api3('Contribution', 'create', [
        'financial_type_id' => 'Donation',
        'receive_date' => '2022-11-11',
        'total_amount' => 100 + $i,
        'contact_id' => 1,
      ]);
      $this->testContributions[] = $contribution['id'];
    }
  }

  public function tearDown(): void {
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
   * Test InvoiceQueue.create API.
   */
  public function testInvoiceQueueCreate() {
    $params = ['contribution_ids' => $this->testContributions];

    $result = civicrm_api3('InvoiceQueue', 'create', $params);

    $this->assertEquals(0, $result['is_error']);
    $this->assertArrayHasKey('values', $result);
    $this->assertArrayHasKey('queue_name', $result['values']);
    $this->assertArrayHasKey('item_count', $result['values']);
    $this->assertEquals(3, $result['values']['item_count']);
  }

  /**
   * Test InvoiceQueue.create API with invalid parameters.
   */
  public function testInvoiceQueueCreateInvalidParams() {
    // Test without contribution_ids
    $params = [];

    $result = civicrm_api3('InvoiceQueue', 'create', $params);

    $this->assertEquals(1, $result['is_error']);
    $this->assertStringContains('contribution_ids parameter is required', $result['error_message']);
  }

  /**
   * Test InvoiceQueue.create API with empty contribution_ids.
   */
  public function testInvoiceQueueCreateEmptyIds() {
    $params = ['contribution_ids' => []];

    $result = civicrm_api3('InvoiceQueue', 'create', $params);

    $this->assertEquals(1, $result['is_error']);
    $this->assertStringContains('No valid contribution IDs provided', $result['error_message']);
  }

  /**
   * Test InvoiceQueue.getstatus API.
   */
  public function testInvoiceQueueGetStatus() {
    // First create a queue
    $createParams = ['contribution_ids' => $this->testContributions];
    $createResult = civicrm_api3('InvoiceQueue', 'create', $createParams);
    $queueName = $createResult['values']['queue_name'];

    // Get status
    $statusParams = ['queue_name' => $queueName];
    $result = civicrm_api3('InvoiceQueue', 'getstatus', $statusParams);

    $this->assertEquals(0, $result['is_error']);
    $this->assertArrayHasKey('values', $result);
    $this->assertEquals($queueName, $result['values']['queue_name']);
    $this->assertArrayHasKey('total_items', $result['values']);
    $this->assertArrayHasKey('status', $result['values']);
  }

  /**
   * Test InvoiceQueue.process API (create and run in one call).
   */
  public function testInvoiceQueueProcess() {
    $params = [
      'contribution_ids' => array_slice($this->testContributions, 0, 2),
      'title' => 'Test Invoice Processing',
    ];

    $result = civicrm_api3('InvoiceQueue', 'process', $params);

    $this->assertEquals(0, $result['is_error']);
    $this->assertArrayHasKey('values', $result);
    $this->assertArrayHasKey('queue_name', $result['values']);
    $this->assertArrayHasKey('item_count', $result['values']);
    $this->assertArrayHasKey('items_processed', $result['values']);
    $this->assertEquals(2, $result['values']['item_count']);
  }

  /**
   * Test InvoiceQueue.run API.
   */
  public function testInvoiceQueueRun() {
    // First create a queue
    $createParams = ['contribution_ids' => [$this->testContributions[0]]];
    $createResult = civicrm_api3('InvoiceQueue', 'create', $createParams);
    $queueName = $createResult['values']['queue_name'];

    // Run the queue
    $runParams = [
      'queue_name' => $queueName,
      'title' => 'Test Queue Run',
    ];

    $result = civicrm_api3('InvoiceQueue', 'run', $runParams);

    $this->assertEquals(0, $result['is_error']);
    $this->assertArrayHasKey('values', $result);
    $this->assertEquals($queueName, $result['values']['queue_name']);
    $this->assertArrayHasKey('items_processed', $result['values']);
  }

  /**
   * Test custom queue name functionality.
   */
  public function testCustomQueueName() {
    $customName = 'test_custom_queue_' . uniqid();
    
    $params = [
      'contribution_ids' => [$this->testContributions[0]],
      'queue_name' => $customName,
    ];

    $result = civicrm_api3('InvoiceQueue', 'create', $params);

    $this->assertEquals(0, $result['is_error']);
    $this->assertEquals($customName, $result['values']['queue_name']);
  }

  /**
   * Test complete workflow: create, check status, run.
   */
  public function testCompleteWorkflow() {
    $contributionIds = array_slice($this->testContributions, 0, 2);

    // Step 1: Create queue
    $createResult = civicrm_api3('InvoiceQueue', 'create', [
      'contribution_ids' => $contributionIds,
    ]);
    
    $this->assertEquals(0, $createResult['is_error']);
    $queueName = $createResult['values']['queue_name'];

    // Step 2: Check status
    $statusResult = civicrm_api3('InvoiceQueue', 'getstatus', [
      'queue_name' => $queueName,
    ]);
    
    $this->assertEquals(0, $statusResult['is_error']);
    $this->assertEquals('pending', $statusResult['values']['status']);

    // Step 3: Run queue
    $runResult = civicrm_api3('InvoiceQueue', 'run', [
      'queue_name' => $queueName,
    ]);
    
    $this->assertEquals(0, $runResult['is_error']);
    $this->assertGreaterThan(0, $runResult['values']['items_processed']);
  }

}