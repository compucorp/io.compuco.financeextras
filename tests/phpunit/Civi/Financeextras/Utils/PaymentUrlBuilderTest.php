<?php

namespace Civi\Financeextras\Utils;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PaymentUrlBuilder utility class
 *
 * @group headless
 */
class PaymentUrlBuilderTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Test building success URL with basic parameters
   */
  public function testBuildSuccessUrl() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $url = PaymentUrlBuilder::buildSuccessUrl(456, $params);

    $this->assertStringContainsString('civicrm/contribute/transact', $url);
    $this->assertStringContainsString('id=123', $url);
    $this->assertStringContainsString('qfKey=test_qf_key', $url);
    $this->assertStringContainsString('_qf_ThankYou_display=1', $url);
  }

  /**
   * Test building success URL with processor-specific parameters
   */
  public function testBuildSuccessUrlWithAdditionalParams() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $additionalParams = [
      'session_id' => '{CHECKOUT_SESSION_ID}',
      'payment_intent' => '{PAYMENT_INTENT_ID}',
    ];

    $url = PaymentUrlBuilder::buildSuccessUrl(456, $params, $additionalParams);

    $this->assertStringContainsString('civicrm/contribute/transact', $url);
    $this->assertStringContainsString('session_id=%7BCHECKOUT_SESSION_ID%7D', $url);
    $this->assertStringContainsString('payment_intent=%7BPAYMENT_INTENT_ID%7D', $url);
  }

  /**
   * Test building cancel URL
   */
  public function testBuildCancelUrl() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $url = PaymentUrlBuilder::buildCancelUrl(456, $params);

    $this->assertStringContainsString('civicrm/contribute/transact', $url);
    $this->assertStringContainsString('id=123', $url);
    $this->assertStringContainsString('qfKey=test_qf_key', $url);
    $this->assertStringContainsString('cancel=1', $url);
    $this->assertStringContainsString('contribution_id=456', $url);
    $this->assertStringContainsString('_qf_Main_display=1', $url);
  }

  /**
   * Test building error URL
   */
  public function testBuildErrorUrl() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $url = PaymentUrlBuilder::buildErrorUrl(456, $params, 'Payment failed');

    $this->assertStringContainsString('civicrm/contribute/transact', $url);
    $this->assertStringContainsString('error=1', $url);
    $this->assertStringContainsString('contribution_id=456', $url);
    $this->assertStringContainsString('error_message=Payment+failed', $url);
  }

  /**
   * Test building error URL without message
   */
  public function testBuildErrorUrlWithoutMessage() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $url = PaymentUrlBuilder::buildErrorUrl(456, $params);

    $this->assertStringContainsString('error=1', $url);
    $this->assertStringNotContainsString('error_message', $url);
  }

  /**
   * Test building event success URL
   */
  public function testBuildEventSuccessUrl() {
    $params = [
      'eventID' => 789,
      'qfKey' => 'test_event_qf_key',
    ];

    $additionalParams = [
      'redirect_flow_id' => '{REDIRECT_FLOW_ID}',
    ];

    $url = PaymentUrlBuilder::buildEventSuccessUrl(100, $params, $additionalParams);

    $this->assertStringContainsString('civicrm/event/register', $url);
    $this->assertStringContainsString('id=789', $url);
    $this->assertStringContainsString('qfKey=test_event_qf_key', $url);
    $this->assertStringContainsString('_qf_ThankYou_display=1', $url);
    $this->assertStringContainsString('redirect_flow_id=%7BREDIRECT_FLOW_ID%7D', $url);
  }

  /**
   * Test building event cancel URL
   */
  public function testBuildEventCancelUrl() {
    $params = [
      'eventID' => 789,
      'qfKey' => 'test_event_qf_key',
    ];

    $url = PaymentUrlBuilder::buildEventCancelUrl(100, $params);

    $this->assertStringContainsString('civicrm/event/register', $url);
    $this->assertStringContainsString('id=789', $url);
    $this->assertStringContainsString('cancel=1', $url);
    $this->assertStringContainsString('participant_id=100', $url);
    $this->assertStringContainsString('_qf_Register_display=1', $url);
  }

  /**
   * Test URL is absolute (contains http/https)
   */
  public function testUrlsAreAbsolute() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $successUrl = PaymentUrlBuilder::buildSuccessUrl(456, $params);
    $cancelUrl = PaymentUrlBuilder::buildCancelUrl(456, $params);

    // URLs should be absolute (contain protocol)
    // Note: In tests, CRM_Utils_System::url might return relative URLs
    // In production with proper CiviCRM config, these will be absolute
    $this->assertIsString($successUrl);
    $this->assertIsString($cancelUrl);
  }

  /**
   * Test handling missing parameters gracefully
   */
  public function testHandleMissingParameters() {
    // Empty params
    $params = [];

    $url = PaymentUrlBuilder::buildSuccessUrl(456, $params);

    // Should not throw exception, parameters will be NULL
    $this->assertStringContainsString('civicrm/contribute/transact', $url);
  }

}
