<?php

use Civi\Financeextras\Test\Fabricator\PaymentProcessorFabricator;
use Civi\Financeextras\Refund\PaymentProcessor;

/**
 * Tests for the PaymentProcessor class.
 *
 * @group headless
 */
class PaymentProcessorTest extends BaseHeadlessTest {

  public function testGetRefundProcessorIDs() {
    $dummyProcessor = $this->fabricateDummyProcessor();

    $paymentProcessor = new PaymentProcessor();
    $processorIDs = $paymentProcessor->getRefundProcessorIDs();
    $this->assertCount(1, $processorIDs);
    $this->assertEquals($dummyProcessor['id'], $processorIDs[0]);

  }

  private function fabricateDummyProcessor() {
    return PaymentProcessorFabricator::fabricate([
      'name' => "Dummy",
      'payment_processor_type_id' => "Dummy",
      'financial_account_id' => "Payment Processor Account",
    ]);
  }

}
