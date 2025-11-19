<?php

use CRM_Financeextras_BAO_PaymentAttempt as PaymentAttempt;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * Tests for CRM_Financeextras_BAO_PaymentAttempt.
 *
 * @group headless
 */
class CRM_Financeextras_BAO_PaymentAttemptTest extends BaseHeadlessTest {

  /**
   * @var int
   */
  private $contactId;

  /**
   * @var int
   */
  private $contributionId;

  /**
   * Set up test fixtures.
   */
  public function setUp(): void {
    parent::setUp();

    // Create test contact
    $contact = ContactFabricator::fabricate(['contact_type' => 'Individual']);
    $this->contactId = $contact['id'];

    // Create test contribution
    $contribution = ContributionFabricator::fabricate([
      'contact_id' => $this->contactId,
      'total_amount' => 100.00,
      'financial_type_id' => 'Donation',
    ]);
    $this->contributionId = $contribution['id'];
  }

  /**
   * Tests creating a payment attempt record.
   */
  public function testCreate() {
    $params = [
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
      'processor_session_id' => 'cs_test_123',
      'processor_payment_id' => 'pi_test_456',
    ];

    $attempt = PaymentAttempt::create($params);

    $this->assertNotNull($attempt->id);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $attempt->{$key}, "Field {$key} should match");
    }
  }

  /**
   * Tests finding payment attempt by contribution ID.
   */
  public function testFindByContributionId() {
    // Create attempt
    $params = [
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
    ];

    PaymentAttempt::create($params);

    // Find by contribution ID
    $found = PaymentAttempt::findByContributionId($this->contributionId);

    $this->assertNotNull($found);
    $this->assertEquals($this->contributionId, $found['contribution_id']);
    $this->assertEquals($this->contactId, $found['contact_id']);
    $this->assertEquals('stripe', $found['processor_type']);
  }

  /**
   * Tests finding payment attempt by session ID.
   */
  public function testFindBySessionId() {
    $sessionId = 'cs_test_session_123';

    // Create attempt
    $params = [
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
      'processor_session_id' => $sessionId,
    ];

    PaymentAttempt::create($params);

    // Find by session ID
    $found = PaymentAttempt::findBySessionId($sessionId, 'stripe');

    $this->assertNotNull($found);
    $this->assertEquals($sessionId, $found['processor_session_id']);
    $this->assertEquals('stripe', $found['processor_type']);
  }

  /**
   * Tests finding payment attempt by session ID with wrong processor type returns null.
   */
  public function testFindBySessionIdWrongProcessorType() {
    $sessionId = 'cs_test_session_456';

    // Create Stripe attempt
    PaymentAttempt::create([
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
      'processor_session_id' => $sessionId,
    ]);

    // Try to find with wrong processor type
    $found = PaymentAttempt::findBySessionId($sessionId, 'gocardless');

    $this->assertNull($found);
  }

  /**
   * Tests finding payment attempt by payment ID.
   */
  public function testFindByPaymentId() {
    $paymentId = 'pi_test_payment_789';

    // Create attempt
    $params = [
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
      'processor_payment_id' => $paymentId,
    ];

    PaymentAttempt::create($params);

    // Find by payment ID
    $found = PaymentAttempt::findByPaymentId($paymentId, 'stripe');

    $this->assertNotNull($found);
    $this->assertEquals($paymentId, $found['processor_payment_id']);
    $this->assertEquals('stripe', $found['processor_type']);
  }

  /**
   * Tests finding payment attempt by payment ID with wrong processor type returns null.
   */
  public function testFindByPaymentIdWrongProcessorType() {
    $paymentId = 'pi_test_payment_101';

    // Create Stripe attempt
    PaymentAttempt::create([
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
      'processor_payment_id' => $paymentId,
    ]);

    // Try to find with wrong processor type
    $found = PaymentAttempt::findByPaymentId($paymentId, 'gocardless');

    $this->assertNull($found);
  }

  /**
   * Tests getStatuses returns correct status options.
   */
  public function testGetStatuses() {
    $statuses = PaymentAttempt::getStatuses();

    $this->assertIsArray($statuses);
    $this->assertArrayHasKey('pending', $statuses);
    $this->assertArrayHasKey('completed', $statuses);
    $this->assertArrayHasKey('failed', $statuses);
    $this->assertArrayHasKey('cancelled', $statuses);

    $this->assertEquals('Pending', $statuses['pending']);
    $this->assertEquals('Completed', $statuses['completed']);
    $this->assertEquals('Failed', $statuses['failed']);
    $this->assertEquals('Cancelled', $statuses['cancelled']);
  }

  /**
   * Tests updating an existing payment attempt.
   */
  public function testUpdate() {
    // Create initial attempt
    $params = [
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
    ];

    $attempt = PaymentAttempt::create($params);
    $attemptId = $attempt->id;

    // Update with session ID
    $updateParams = [
      'id' => $attemptId,
      'processor_session_id' => 'cs_test_updated_123',
      'status' => 'completed',
    ];

    $updated = PaymentAttempt::create($updateParams);

    $this->assertEquals($attemptId, $updated->id);
    $this->assertEquals('cs_test_updated_123', $updated->processor_session_id);
    $this->assertEquals('completed', $updated->status);
  }

  /**
   * Tests that contribution_id has UNIQUE constraint.
   *
   * This test verifies that attempting to create duplicate attempts for the
   * same contribution will fail, maintaining data integrity.
   */
  public function testContributionIdUniqueConstraint() {
    // Create first attempt
    $params = [
      'contribution_id' => $this->contributionId,
      'contact_id' => $this->contactId,
      'processor_type' => 'stripe',
      'status' => 'pending',
    ];

    PaymentAttempt::create($params);

    // Try to create duplicate attempt
    $this->expectException(\Exception::class);
    PaymentAttempt::create($params);
  }

  /**
   * Tests finding payment attempt returns NULL when not found.
   */
  public function testFindByContributionIdNotFound() {
    $nonExistentId = 999999;
    $found = PaymentAttempt::findByContributionId($nonExistentId);

    $this->assertNull($found);
  }

  /**
   * Tests finding payment attempt by session ID returns NULL when not found.
   */
  public function testFindBySessionIdNotFound() {
    $found = PaymentAttempt::findBySessionId('cs_nonexistent', 'stripe');

    $this->assertNull($found);
  }

  /**
   * Tests finding payment attempt by payment ID returns NULL when not found.
   */
  public function testFindByPaymentIdNotFound() {
    $found = PaymentAttempt::findByPaymentId('pi_nonexistent', 'stripe');

    $this->assertNull($found);
  }

}
