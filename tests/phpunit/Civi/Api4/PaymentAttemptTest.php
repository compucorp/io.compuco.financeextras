<?php

use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Api4\PaymentAttempt;
use Civi\Api4\PaymentProcessor;

/**
 * PaymentAttempt API Test Case.
 *
 * Tests for the PaymentAttempt Api4 entity.
 *
 * @group headless
 */
class Civi_Api4_PaymentAttemptTest extends BaseHeadlessTest {

  private $contactId;
  private $contributionId;
  private $paymentProcessorId;

  public function setUp(): void {
    parent::setUp();

    // Create test contact
    $this->contactId = Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', 'Test')
      ->addValue('last_name', 'Donor')
      ->execute()
      ->first()['id'];

    // Create test payment processor using Dummy type (built-in test processor)
    $this->paymentProcessorId = PaymentProcessor::create(FALSE)
      ->addValue('name', 'Test Processor')
      ->addValue('payment_processor_type_id:name', 'Dummy')
      ->addValue('class_name', 'Payment_Dummy')
      ->addValue('is_active', 1)
      ->addValue('is_test', 0)
      ->execute()
      ->first()['id'];

    // Create test contribution
    $this->contributionId = Contribution::create(FALSE)
      ->addValue('contact_id', $this->contactId)
      ->addValue('financial_type_id:name', 'Donation')
      ->addValue('total_amount', 100.00)
      ->addValue('currency', 'GBP')
      ->addValue('contribution_status_id:name', 'Pending')
      ->execute()
      ->first()['id'];
  }

  /**
   * Test creating a PaymentAttempt with required fields.
   */
  public function testCreatePaymentAttemptWithRequiredFields() {
    $created = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->execute()
      ->first();

    // Fetch the full record to get default values
    $attempt = PaymentAttempt::get(FALSE)
      ->addWhere('id', '=', $created['id'])
      ->execute()
      ->first();

    $this->assertNotEmpty($attempt['id']);
    $this->assertEquals($this->contributionId, $attempt['contribution_id']);
    $this->assertEquals('stripe', $attempt['processor_type']);
    $this->assertEquals('pending', $attempt['status']);
    $this->assertNotEmpty($attempt['created_date']);
  }

  /**
   * Test creating a PaymentAttempt with all fields.
   */
  public function testCreatePaymentAttemptWithAllFields() {
    $attempt = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('contact_id', $this->contactId)
      ->addValue('payment_processor_id', $this->paymentProcessorId)
      ->addValue('processor_type', 'stripe')
      ->addValue('processor_session_id', 'cs_test_123')
      ->addValue('processor_payment_id', 'pi_test_456')
      ->addValue('status', 'completed')
      ->execute()
      ->first();

    $this->assertNotEmpty($attempt['id']);
    $this->assertEquals($this->contributionId, $attempt['contribution_id']);
    $this->assertEquals($this->contactId, $attempt['contact_id']);
    $this->assertEquals($this->paymentProcessorId, $attempt['payment_processor_id']);
    $this->assertEquals('stripe', $attempt['processor_type']);
    $this->assertEquals('cs_test_123', $attempt['processor_session_id']);
    $this->assertEquals('pi_test_456', $attempt['processor_payment_id']);
    $this->assertEquals('completed', $attempt['status']);
  }

  /**
   * Test that contribution_id is required.
   */
  public function testCreateWithoutContributionIdFails() {
    $this->expectException(\CRM_Core_Exception::class);

    PaymentAttempt::create(FALSE)
      ->addValue('processor_type', 'stripe')
      ->execute();
  }

  /**
   * Test that processor_type is required.
   */
  public function testCreateWithoutProcessorTypeFails() {
    $this->expectException(\CRM_Core_Exception::class);

    PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->execute();
  }

  /**
   * Test unique constraint on contribution_id.
   */
  public function testUniqueContributionConstraint() {
    // Create first attempt
    PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->execute();

    // Try to create duplicate - should fail
    $this->expectException(\CRM_Core_Exception::class);

    PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->execute();
  }

  /**
   * Test retrieving PaymentAttempt by contribution_id.
   */
  public function testGetByContributionId() {
    $created = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->addValue('processor_session_id', 'cs_test_789')
      ->execute()
      ->first();

    $retrieved = PaymentAttempt::get(FALSE)
      ->addWhere('contribution_id', '=', $this->contributionId)
      ->execute()
      ->first();

    $this->assertEquals($created['id'], $retrieved['id']);
    $this->assertEquals('cs_test_789', $retrieved['processor_session_id']);
  }

  /**
   * Test retrieving PaymentAttempt by processor session ID.
   */
  public function testGetByProcessorSessionId() {
    PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->addValue('processor_session_id', 'cs_test_unique')
      ->execute();

    $retrieved = PaymentAttempt::get(FALSE)
      ->addWhere('processor_session_id', '=', 'cs_test_unique')
      ->addWhere('processor_type', '=', 'stripe')
      ->execute()
      ->first();

    $this->assertEquals('cs_test_unique', $retrieved['processor_session_id']);
    $this->assertEquals('stripe', $retrieved['processor_type']);
  }

  /**
   * Test updating PaymentAttempt status.
   */
  public function testUpdateStatus() {
    $attempt = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->addValue('status', 'pending')
      ->execute()
      ->first();

    PaymentAttempt::update(FALSE)
      ->addWhere('id', '=', $attempt['id'])
      ->addValue('status', 'completed')
      ->addValue('processor_payment_id', 'pi_completed_123')
      ->execute();

    $updated = PaymentAttempt::get(FALSE)
      ->addWhere('id', '=', $attempt['id'])
      ->execute()
      ->first();

    $this->assertEquals('completed', $updated['status']);
    $this->assertEquals('pi_completed_123', $updated['processor_payment_id']);
  }

  /**
   * Test deleting PaymentAttempt.
   */
  public function testDelete() {
    $attempt = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->execute()
      ->first();

    PaymentAttempt::delete(FALSE)
      ->addWhere('id', '=', $attempt['id'])
      ->execute();

    $count = PaymentAttempt::get(FALSE)
      ->addWhere('id', '=', $attempt['id'])
      ->execute()
      ->count();

    $this->assertEquals(0, $count);
  }

  /**
   * Test cascade delete when contribution is deleted.
   */
  public function testCascadeDeleteWithContribution() {
    $attempt = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('processor_type', 'stripe')
      ->execute()
      ->first();

    // Delete contribution
    Contribution::delete(FALSE)
      ->addWhere('id', '=', $this->contributionId)
      ->execute();

    // PaymentAttempt should also be deleted (CASCADE)
    $count = PaymentAttempt::get(FALSE)
      ->addWhere('id', '=', $attempt['id'])
      ->execute()
      ->count();

    $this->assertEquals(0, $count);
  }

  /**
   * Test SET NULL when contact is deleted.
   */
  public function testSetNullWhenContactDeleted() {
    // Create separate contact for testing deletion (not tied to contribution)
    $tempContactId = Contact::create(FALSE)
      ->addValue('contact_type', 'Individual')
      ->addValue('first_name', 'Temp')
      ->addValue('last_name', 'Contact')
      ->execute()
      ->first()['id'];

    $attempt = PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $this->contributionId)
      ->addValue('contact_id', $tempContactId)
      ->addValue('processor_type', 'stripe')
      ->execute()
      ->first();

    // Delete the temp contact (use skip_undelete to force permanent deletion)
    Contact::delete(FALSE)
      ->addWhere('id', '=', $tempContactId)
      ->setUseTrash(FALSE)
      ->execute();

    // PaymentAttempt should still exist but with NULL contact_id
    $retrieved = PaymentAttempt::get(FALSE)
      ->addWhere('id', '=', $attempt['id'])
      ->execute()
      ->first();

    $this->assertNull($retrieved['contact_id']);
  }

  /**
   * Test different processor types.
   */
  public function testDifferentProcessorTypes() {
    $processors = ['stripe', 'gocardless', 'itas', 'paypal'];

    foreach ($processors as $processor) {
      // Create new contribution for each processor
      $contribId = Contribution::create(FALSE)
        ->addValue('contact_id', $this->contactId)
        ->addValue('financial_type_id:name', 'Donation')
        ->addValue('total_amount', 50.00)
        ->addValue('currency', 'GBP')
        ->addValue('contribution_status_id:name', 'Pending')
        ->execute()
        ->first()['id'];

      $attempt = PaymentAttempt::create(FALSE)
        ->addValue('contribution_id', $contribId)
        ->addValue('processor_type', $processor)
        ->execute()
        ->first();

      $this->assertEquals($processor, $attempt['processor_type']);
    }
  }

  /**
   * Test all valid statuses.
   */
  public function testAllStatuses() {
    $statuses = ['pending', 'completed', 'failed', 'cancelled'];

    foreach ($statuses as $status) {
      // Create new contribution for each status
      $contribId = Contribution::create(FALSE)
        ->addValue('contact_id', $this->contactId)
        ->addValue('financial_type_id:name', 'Donation')
        ->addValue('total_amount', 25.00)
        ->addValue('currency', 'GBP')
        ->addValue('contribution_status_id:name', 'Pending')
        ->execute()
        ->first()['id'];

      $attempt = PaymentAttempt::create(FALSE)
        ->addValue('contribution_id', $contribId)
        ->addValue('processor_type', 'stripe')
        ->addValue('status', $status)
        ->execute()
        ->first();

      $this->assertEquals($status, $attempt['status']);
    }
  }

  /**
   * Test querying by processor type and status.
   */
  public function testFilterByProcessorTypeAndStatus() {
    // Create multiple attempts with different combinations
    $contribId1 = Contribution::create(FALSE)
      ->addValue('contact_id', $this->contactId)
      ->addValue('financial_type_id:name', 'Donation')
      ->addValue('total_amount', 10.00)
      ->addValue('currency', 'GBP')
      ->addValue('contribution_status_id:name', 'Pending')
      ->execute()
      ->first()['id'];

    $contribId2 = Contribution::create(FALSE)
      ->addValue('contact_id', $this->contactId)
      ->addValue('financial_type_id:name', 'Donation')
      ->addValue('total_amount', 20.00)
      ->addValue('currency', 'GBP')
      ->addValue('contribution_status_id:name', 'Pending')
      ->execute()
      ->first()['id'];

    PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $contribId1)
      ->addValue('processor_type', 'stripe')
      ->addValue('status', 'completed')
      ->execute();

    PaymentAttempt::create(FALSE)
      ->addValue('contribution_id', $contribId2)
      ->addValue('processor_type', 'stripe')
      ->addValue('status', 'pending')
      ->execute();

    $completed = PaymentAttempt::get(FALSE)
      ->addWhere('processor_type', '=', 'stripe')
      ->addWhere('status', '=', 'completed')
      ->execute()
      ->count();

    $this->assertGreaterThanOrEqual(1, $completed);
  }

}
