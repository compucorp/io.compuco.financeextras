<?php

use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * Tests for the Refund class.
 *
 * @group headless
 */
class PaymentTest extends BaseHeadlessTest {

  protected static array $contribution = [];

  public static function setupBeforeClass(): void {
    $contact = ContactFabricator::fabricate();
    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 200,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'trxn_id' => md5(time()),
      'currency' => 'GBP',
      'contribution_status_id' => 2,
    ];

    self::$contribution = ContributionFabricator::fabricate($contributionParams);
  }

  public function setUp(): void {
  }

  public function testMakingPartialPaymentUpdatesContributionToPartiallyPaid(): void {
    civicrm_api3('Payment', 'create', [
      'contribution_id' => self::$contribution['id'],
      'total_amount' => 100,
      'trxn_date' => date('Y-m-d H:i:s'),
      'trxn_id' => self::$contribution['trxn_id'],
      'is_send_contribution_notification' => FALSE,
    ]);
    $contribution = civicrm_api3('Contribution', 'getSingle', ['id' => self::$contribution['id']]);

    $this->assertEquals('Partially paid', $contribution['contribution_status']);
  }

  public function testMakingFullPaymentUpdatesContributionToCompleted(): void {
    civicrm_api3('Payment', 'create', [
      'contribution_id' => self::$contribution['id'],
      'total_amount' => 200,
      'trxn_date' => date('Y-m-d H:i:s'),
      'trxn_id' => self::$contribution['trxn_id'],
      'is_send_contribution_notification' => FALSE,
    ]);
    $contribution = civicrm_api3('Contribution', 'getSingle', ['id' => self::$contribution['id']]);

    $this->assertEquals('Completed', $contribution['contribution_status']);
  }

  public function testZeroAmountContributionIsCreatedAsCompleted(): void {
    $contact = ContactFabricator::fabricate();
    self::$contribution = ContributionFabricator::fabricate([
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 0.00,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'currency' => 'GBP',
    ]);

    $contribution = civicrm_api3('Contribution', 'getSingle', ['id' => self::$contribution['id']]);

    $this->assertEquals('Completed', $contribution['contribution_status']);
  }

}
