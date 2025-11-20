<?php

namespace Civi\Financeextras\Hook\Post;

use BaseHeadlessTest;

/**
 * @group headless
 */
class ContributionCreationTest extends BaseHeadlessTest {

  private $firstCompany;

  private $secondCompany;

  public function setUp(): void {
    parent::setUp();

    // Both "Donation" And "Member Dues" are financial types that are shipped with the test
    // database by default, and their income account has the same name.
    $this->firstCompany = $this->createCompany(1);
    $this->updateFinancialAccountOwner('Donation', $this->firstCompany['contact_id']);

    $this->secondCompany = $this->createCompany(2);
    $this->updateFinancialAccountOwner('Member Dues', $this->secondCompany['contact_id']);
  }

  public function testOwnerOrganizationIsSetToFirstLineItemIncomeAccountOwner() {
    $params = [
      'price_field_id' => 1,
      'label' => 'Price Field 2',
      'amount' => 100,
      'financial_type_id' => 'Donation',
    ];
    $secondPriceFieldValue = civicrm_api3('PriceFieldValue', 'create', $params);

    // Creating order with two line items,
    // where each line item has different
    // financial_type_id
    $donationFinancialTypeId = 1;
    $memberDuesFinancialTypeId = 2;
    $orderParams = [
      'contact_id' => 1,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 'Pending',
      'line_items' => [
        '0' => [
          'line_item' => [
            '0' => [
              'price_field_id' => '1',
              'price_field_value_id' => '1',
              'label' => 'Price Field 1',
              'field_title' => 'Price Field 1',
              'qty' => 1,
              'unit_price' => '100',
              'line_total' => '100',
              'financial_type_id' => $memberDuesFinancialTypeId,
              'entity_table' => 'civicrm_contribution',
            ],
          ],
        ],
        '1' => [
          'line_item' => [
            '0' => [
              'price_field_id' => '1',
              'price_field_value_id' => $secondPriceFieldValue['id'],
              'label' => 'Price Field 2',
              'field_title' => 'Price Field 2',
              'qty' => 1,
              'unit_price' => '100',
              'line_total' => '100',
              'financial_type_id' => $donationFinancialTypeId,
              'entity_table' => 'civicrm_contribution',
            ],
          ],
        ],
      ],
    ];
    $order = civicrm_api3('Order', 'create', $orderParams);

    $contributionId = key($order['values']);
    $contributionOwnerCompany = \CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation::getOwnerOrganisationCompany($contributionId);

    $this->assertEquals($this->secondCompany['contact_id'], $contributionOwnerCompany['contact_id']);
  }

  public function testOwnerOrganizationIsSetBasedOnTheContributionFinancialTypeIncomeAccountIfNoLineItemsExist() {
    $contribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Member Dues',
      'receive_date' => '2023-01-01',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    $contributionId = key($contribution['values']);
    $contributionOwnerCompany = \CRM_Financeextras_CustomGroup_ContributionOwnerOrganisation::getOwnerOrganisationCompany($contributionId);

    $this->assertEquals($this->secondCompany['contact_id'], $contributionOwnerCompany['contact_id']);
  }

  public function testContributionInvoiceNumberIsSetToCompanyNextInvoiceNumber() {
    $contribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Member Dues',
      'receive_date' => '2023-01-01',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);
    $contributionId = key($contribution['values']);

    $contributionInvoiceNumber = civicrm_api3('Contribution', 'getvalue', [
      'return' => 'invoice_number',
      'id' => $contributionId,
    ]);

    $this->assertEquals('INV2_000002', $contributionInvoiceNumber);
  }

  /**
   * @dataProvider withLeadingZerosInvoiceNumbersProvider
   */
  public function testIncrementedInvoiceNumberRespectsLeadingZeros($currentNextInvoiceNumber, $newNextInvoiceNumber) {
    \CRM_Financeextras_BAO_Company::create(['id' => $this->secondCompany['id'], 'next_invoice_number' => $currentNextInvoiceNumber]);

    civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Member Dues',
      'receive_date' => '2023-01-01',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);

    $company = \CRM_Financeextras_BAO_Company::getById($this->secondCompany['id']);
    $this->assertEquals($newNextInvoiceNumber, $company->next_invoice_number);
  }

  public function withLeadingZerosInvoiceNumbersProvider() {
    return [
      ['01', '02'],
      ['09', '10'],
      ['001', '002'],
      ['090', '091'],
      ['099', '100'],
      ['0000055', '0000056'],
    ];
  }

  /**
   * @dataProvider noLeadingZerosInvoiceNumbersProvider
   */
  public function testIncrementedInvoiceNumberWouldKeepIncrementingEvenIfReachedLeadingZerosLimit($currentNextInvoiceNumber, $newNextInvoiceNumber) {
    \CRM_Financeextras_BAO_Company::create(['id' => $this->firstCompany['id'], 'next_invoice_number' => $currentNextInvoiceNumber]);

    civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2023-01-01',
      'total_amount' => 100,
      'contact_id' => 1,
    ]);

    $company = \CRM_Financeextras_BAO_Company::getById($this->firstCompany['id']);
    $this->assertEquals($newNextInvoiceNumber, $company->next_invoice_number);
  }

  public function noLeadingZerosInvoiceNumbersProvider() {
    return [
      ['9', '10'],
      ['15', '16'],
      ['99', '100'],
      ['998', '999'],
      ['999', '1000'],
    ];
  }

}
