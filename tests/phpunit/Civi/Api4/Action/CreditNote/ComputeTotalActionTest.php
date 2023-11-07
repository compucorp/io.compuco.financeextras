<?php

use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;

/**
 * CreditNote.ComputeTotalAction API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_ComputeTotalActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * Test credit note compute action returns expected fields.
   */
  public function testComputeTotalActionReturnsExpectedFields() {
    $items = [];
    $items[] = $this->getCreditNoteLineData(
      ['quantity' => 10, 'unit_price' => 10, 'tax_rate' => 10, 'tax_name' => 'taxes']
    );
    $items[] = $this->getCreditNoteLineData(
      ['quantity' => 5, 'unit_price' => 10]
    );

    $computedTotal = CreditNote::computeTotal()
      ->setLineItems($items)
      ->execute()
      ->jsonSerialize()[0];

    $this->assertArrayHasKey('taxRates', $computedTotal);
    $this->assertArrayHasKey('totalBeforeTax', $computedTotal);
    $this->assertArrayHasKey('totalAfterTax', $computedTotal);
  }

  /**
   * Test credit note total is calculated appropraitely.
   */
  public function testComputeTotalActionReturnsExpectedTotal() {
    $items = [];
    $items[] = $this->getCreditNoteLineData(
      ['quantity' => 10, 'unit_price' => 10, 'tax_rate' => 10, 'tax_name' => 'taxes']
    );
    $items[] = $this->getCreditNoteLineData(
      ['quantity' => 5, 'unit_price' => 10]
    );

    $computedTotal = CreditNote::computeTotal()
      ->setLineItems($items)
      ->execute()
      ->jsonSerialize()[0];

    $this->assertEquals($computedTotal['totalBeforeTax'], 150);
    $this->assertEquals($computedTotal['totalAfterTax'], 160);
  }

  /**
   * Test credit note tax rates is computed as epxected.
   */
  public function testComputeTotalActionReturnsExpectedTaxRates() {
    $items = [];
    $items[] = $this->getCreditNoteLineData(
      ['quantity' => 10, 'unit_price' => 10, 'tax_rate' => 10, 'tax_name' => 'taxes']
    );
    $items[] = $this->getCreditNoteLineData(
      ['quantity' => 5, 'unit_price' => 10, 'tax_rate' => 2, 'tax_name' => 'taxes']
    );

    $computedTotal = CreditNote::computeTotal()
      ->setLineItems($items)
      ->execute()
      ->jsonSerialize()[0];

    $this->assertNotEmpty($computedTotal['taxRates']);
    $this->assertCount(2, $computedTotal['taxRates']);

    // Ensure the tax rates are sorted in ascending order of rate.
    $this->assertEquals($computedTotal['taxRates'][0]['rate'], 2);
    $this->assertEquals($computedTotal['taxRates'][0]['value'], 1);
    $this->assertEquals($computedTotal['taxRates'][1]['rate'], 10);
    $this->assertEquals($computedTotal['taxRates'][1]['value'], 10);
  }

  /**
   * Test compute action doesn't throw error for empty line items.
   */
  public function testComputeTotalActionReturnsEmptyResultForEmptyLineItems() {
    $items = [];

    $computedTotal = CreditNote::computeTotal()
      ->setLineItems($items)
      ->execute()
      ->jsonSerialize()[0];

    $this->assertArrayHasKey('taxRates', $computedTotal);
    $this->assertArrayHasKey('totalBeforeTax', $computedTotal);
    $this->assertArrayHasKey('totalAfterTax', $computedTotal);

    $this->assertEmpty($computedTotal['taxRates']);
    $this->assertEmpty($computedTotal['totalAfterTax']);
    $this->assertEmpty($computedTotal['totalBeforeTax']);
  }

}
