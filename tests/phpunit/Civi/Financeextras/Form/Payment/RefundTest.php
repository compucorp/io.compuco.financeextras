<?php

use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * Tests for the Refund form class.
 *
 * @group headless
 */
class CRM_Financeextras_Form_Payment_RefundTest extends BaseHeadlessTest {

  private $refundCreationForm;

  public function setUp() {
    $formController = new CRM_Core_Controller();
    $this->refundCreationForm = new CRM_Financeextras_Form_Payment_Refund();
    $this->refundCreationForm->controller = $formController;
    $this->refundCreationForm->setVar('_id', 10);
    $this->refundCreationForm->buildForm();
  }

  public function testPreProcess() {
    $this->contributionID = 10;

    $contact = ContactFabricator::fabricate();
    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 10,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'trxn_id' => md5(time()),
      'currency' => 'GBP',
    ];

    $contribution = ContributionFabricator::fabricate($contributionParams);

    $paymentRefundPermission = new \Civi\Financeextras\Payment\Refund($contribution['id']);
    if (!$paymentRefundPermission->contactHasRefundPermission()) {
      return;
    }
    $paymentTransactions = civicrm_api3('Payment', 'get', [
      'contribution_id' => $this->contributionID,
      'status_id' => 1,
    ])['values'];

    $this->assertTrue((array) $paymentTransactions);

  }

  public function buildQuickForm() {
    $refundCreationForm->buildForm();

    $amountField = $this->refundCreationForm->getElement('amount');
    $reasonField = $this->refundCreationForm->getElement('reason');

    $this->assertTrue(is_object($amountField));
    $this->assertTrue(is_object($reasonField));
  }

  public function testPostProcess() {
    $contact = ContactFabricator::fabricate();
    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 10,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'trxn_id' => md5(time()),
      'currency' => 'GBP',
    ];

    $contribution = ContributionFabricator::fabricate($contributionParams);
    $this->refundCreationForm->setVar('_id', $contribution['id']);
    $this->refundCreationForm->setVar('amount', 10);
    $this->refundCreationForm->setVar('available_amount', 5);
    $this->refundCreationForm->setVar('contact', 'test household12345');
    $this->refundCreationForm->setVar('reason', 'requested_by_customer');
    $this->refundCreationForm->setVar('payment_processor_id', 6);
    $this->refundCreationForm->setVar('currency', 'GBP');
    $this->refundCreationForm->setVar('trxn_id', 'ch_3LRFgZ4Gm7qStDxg06uWJ14J');

    $refundResult = civicrm_api3('PaymentProcessor', 'refund', [
      'payment_processor_id' => 6,
      'contribution_id' => $contribution['id'],
      'available_amount' => 5,
      'trxn_id' => 'ch_3LRFgZ4Gm7qStDxg06uWJ14J',
      'contact' => 'test household12345',
      'reason' => 'requested_by_customer',
      'amount' => 10,
      'currency' => 'GBP',
    ])['values'];

    $this->assertTrue((string) $refundResult['return_id']);
  }

  public function testNotPostProcess() {
    $contact = ContactFabricator::fabricate();
    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 10,
      'contact_id' => $contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'trxn_id' => md5(time()),
      'currency' => 'GBP',
    ];

    $contribution = ContributionFabricator::fabricate($contributionParams);
    $this->refundCreationForm->setVar('_id', $contribution['id']);
    $this->refundCreationForm->setVar('amount', 10);
    $this->refundCreationForm->setVar('available_amount', 5);
    $this->refundCreationForm->setVar('contact', 'test household12345');
    $this->refundCreationForm->setVar('reason', 'requested_by_customer');
    $this->refundCreationForm->setVar('payment_processor_id', 6);
    $this->refundCreationForm->setVar('currency', 'GBP');
    $this->refundCreationForm->setVar('trxn_id', 'ch_3LRFgZ4Gm7qStDxg06uWJdfdfddf14J');

    $refundResult = civicrm_api3('PaymentProcessor', 'refund', [
      'payment_processor_id' => 6,
      'contribution_id' => $contribution['id'],
      'available_amount' => 5,
      'trxn_id' => 'ch_3LRFgZ4Gm7qStDxg06uWJdfdfddf14J',
      'contact' => 'test household12345',
      'reason' => 'requested_by_customer',
      'amount' => 10,
      'currency' => 'GBP',
    ])['values'];

    $this->assertFalse((string) $refundResult['return_id']);
  }

}
