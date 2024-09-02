<?php

use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;
use Civi\Financeextras\Test\Fabricator\PaymentProcessorFabricator;
use Civi\Financeextras\Payment\Refund;

/**
 * Tests for the Refund class.
 *
 * @group headless
 */
class RefundTest extends BaseHeadlessTest {

  public function testIsEligibleForRefund() {
    $contact = ContactFabricator::fabricate();
    $paymentProcessor = PaymentProcessorFabricator::fabricate([
      'name'  => "Dummy",
      'payment_processor_type_id' => "Dummy",
      'financial_account_id' => "Payment Processor Account",
    ]);
    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 200,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'trxn_id' => md5(time()),
      'payment_processor' => $paymentProcessor['id'],
      'currency' => 'GBP',
    ];

    $contribution = ContributionFabricator::fabricate($contributionParams);

    $refundPayment = new Refund($contribution['id']);
    $isEligible = $refundPayment->isEligibleForRefund();
    $this->assertTrue($isEligible);

  }

  public function testIsNotEligibleForRefund() {
    $contact = ContactFabricator::fabricate();
    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 200,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'trxn_id' => md5(time()),
      'currency' => 'GBP',
    ];

    $contribution = ContributionFabricator::fabricate($contributionParams);

    $refundPayment = new Refund($contribution['id']);
    $isEligible = $refundPayment->isEligibleForRefund();
    $this->assertFalse($isEligible);

  }

}
