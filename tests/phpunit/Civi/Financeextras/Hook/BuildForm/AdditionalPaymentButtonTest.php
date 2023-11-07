<?php

use Civi\Financeextras\Hook\BuildForm\AdditionalPaymentButton;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;
use Civi\Financeextras\Test\Fabricator\PaymentProcessorFabricator;

/**
 * Class AdditionalPaymentButtonTest
 *
 * @group headless
 */
class AdditionalPaymentButtonTest extends BaseHeadlessTest {

  private $additionalPaymentForm;

  public function setUp() {
    $formController = new CRM_Core_Controller();
    $this->additionalPaymentForm = new CRM_Contribute_Form_AdditionalPayment();
    $this->additionalPaymentForm->controller = $formController;
  }

  public function testSubmitRefundButtonLinkAddedToAdditionalPaymentForm() {
    $contact = ContactFabricator::fabricate();
    $paymentProcessor = PaymentProcessorFabricator::fabricate([
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
    $this->additionalPaymentForm->setVar('_id', $contribution['id']);

    if (AdditionalPaymentButton::shouldHandle($this->additionalPaymentForm, 'CRM_Contribute_Form_AdditionalPayment')) {
      $additionalBuildFormHook = new AdditionalPaymentButton($this->additionalPaymentForm);
      $additionalBuildFormHook->handle();
    }

    $html = CRM_Core_Region::instance('form-bottom')->render('');
    $this->assertTrue((boolean) $html);
  }

  public function testRefundButtonAddedToNotLiveAdditionalPaymentForm() {
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
    $this->additionalPaymentForm->setVar('_id', $contribution['id']);

    if (AdditionalPaymentButton::shouldHandle($this->additionalPaymentForm, 'CRM_Contribute_Form_AdditionalPayment')) {
      $additionalBuildFormHook = new AdditionalPaymentButton($this->additionalPaymentForm);
      $additionalBuildFormHook->handle();
    }

    $html = CRM_Core_Region::instance('form-bottom')->render('');
    $this->assertFalse((boolean) $html);
  }

}
