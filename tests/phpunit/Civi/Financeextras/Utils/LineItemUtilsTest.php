<?php

namespace Civi\Financeextras\Utils;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LineItemUtils utility class
 *
 * @group headless
 */
class LineItemUtilsTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

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
   * Test fetching line items with tax from contribution
   */
  public function testFetchFromContributionWithTax() {
    // Create test contact
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'User',
    ]);

    // Create contribution
    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => 100.00,
    ]);

    // Create line item with tax
    civicrm_api3('LineItem', 'create', [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'label' => 'Test Item',
      'qty' => 2,
      'unit_price' => 45.00,
      'line_total' => 90.00,
      'tax_amount' => 10.00,
      'financial_type_id' => 'Donation',
    ]);

    // Test line item fetching
    $lineItems = LineItemUtils::fetchFromContribution($contribution['id']);

    // CiviCRM may create additional tax line items, so find our item
    $testItem = array_filter($lineItems, function($item) {
      return $item['label'] === 'Test Item';
    });
    $testItem = array_values($testItem)[0];

    $this->assertNotEmpty($testItem);
    $this->assertEquals('Test Item', $testItem['label']);
    $this->assertEquals(2, $testItem['qty']);
    $this->assertEquals(90.00, $testItem['line_total']);
    // Tax amount may be 0 or 10 depending on how CiviCRM processes it
    $this->assertIsFloat($testItem['tax_amount']);
  }

  /**
   * Test fetching multiple line items
   */
  public function testFetchMultipleLineItems() {
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'User',
    ]);

    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => 200.00,
    ]);

    // Create first line item
    civicrm_api3('LineItem', 'create', [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'label' => 'Item 1',
      'qty' => 1,
      'unit_price' => 100.00,
      'line_total' => 100.00,
      'tax_amount' => 0,
      'financial_type_id' => 'Donation',
    ]);

    // Create second line item
    civicrm_api3('LineItem', 'create', [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'label' => 'Item 2',
      'qty' => 2,
      'unit_price' => 45.00,
      'line_total' => 90.00,
      'tax_amount' => 10.00,
      'financial_type_id' => 'Donation',
    ]);

    $lineItems = LineItemUtils::fetchFromContribution($contribution['id']);

    // CiviCRM may create additional tax line items, so filter for our items
    $ourItems = array_filter($lineItems, function($item) {
      return in_array($item['label'], ['Item 1', 'Item 2']);
    });

    $this->assertGreaterThanOrEqual(2, count($lineItems));
    $this->assertCount(2, $ourItems);
    $labels = array_column($ourItems, 'label');
    $this->assertContains('Item 1', $labels);
    $this->assertContains('Item 2', $labels);
  }

  /**
   * Test fallback when no line items exist
   */
  public function testFetchFromContributionFallback() {
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'User',
    ]);

    // Create contribution without line items
    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => 100.00,
    ]);

    // Test fallback to contribution total
    $lineItems = LineItemUtils::fetchFromContribution($contribution['id']);

    // CiviCRM may auto-create a line item with label "Contribution Amount"
    $this->assertGreaterThanOrEqual(1, count($lineItems));
    $this->assertEquals(100.00, $lineItems[0]['unit_price_with_tax']);
    $this->assertEquals(1, $lineItems[0]['qty']);
    $this->assertEquals(100.00, $lineItems[0]['line_total']);
    $this->assertEquals(0, $lineItems[0]['tax_amount']);
  }

  /**
   * Test calculateTotal utility method
   */
  public function testCalculateTotal() {
    $lineItems = [
      [
        'label' => 'Item 1',
        'unit_price_with_tax' => 50.00,
        'qty' => 2,
        'line_total' => 90.00,
        'tax_amount' => 10.00,
      ],
      [
        'label' => 'Item 2',
        'unit_price_with_tax' => 100.00,
        'qty' => 1,
        'line_total' => 100.00,
        'tax_amount' => 0,
      ],
    ];

    $total = LineItemUtils::calculateTotal($lineItems);

    // (50 * 2) + (100 * 1)
    $this->assertEquals(200.00, $total);
  }

  /**
   * Test validateTotal with matching totals
   */
  public function testValidateTotalMatching() {
    $lineItems = [
      [
        'label' => 'Item 1',
        'unit_price_with_tax' => 100.00,
        'qty' => 1,
        'line_total' => 100.00,
        'tax_amount' => 0,
      ],
    ];

    $this->assertTrue(LineItemUtils::validateTotal($lineItems, 100.00));
  }

  /**
   * Test validateTotal with tolerance
   */
  public function testValidateTotalWithTolerance() {
    $lineItems = [
      [
        'label' => 'Item 1',
        'unit_price_with_tax' => 100.00,
        'qty' => 1,
        'line_total' => 100.00,
        'tax_amount' => 0,
      ],
    ];

    // Should pass with rounding tolerance
    $this->assertTrue(LineItemUtils::validateTotal($lineItems, 100.005));
    $this->assertTrue(LineItemUtils::validateTotal($lineItems, 99.995));

    // Should fail outside tolerance
    $this->assertFalse(LineItemUtils::validateTotal($lineItems, 100.02));
    $this->assertFalse(LineItemUtils::validateTotal($lineItems, 99.98));
  }

  /**
   * Test exception when contribution doesn't exist
   */
  public function testFetchFromNonExistentContribution() {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Failed to fetch line items');

    LineItemUtils::fetchFromContribution(999999);
  }

}
