<?php

use Civi\Api4\CreditNote;
use Civi\Api4\CreditNoteLine;
use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialItem;
use Civi\Api4\FinancialTrxn;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Setup\Manage\AccountsReceivablePaymentMethod;

/**
 * Tests for the CreditNoteImporter APIv3 wrapper.
 *
 * @group headless
 */
class api_v3_CreditNoteImporterTest extends BaseHeadlessTest {

  /**
   * Customer contact used by every test row.
   *
   * @var array
   */
  private $customer;

  /**
   * Owning organisation for the credit note (set up by BaseHeadlessTest).
   *
   * @var array
   */
  private $ownerOrg;

  public function setUp(): void {
    parent::setUp();

    $this->customer = ContactFabricator::fabricate([
      'first_name' => 'Importer',
      'last_name' => 'Customer',
      'external_identifier' => 'CUST-EXT-1',
    ]);

    $this->ownerOrg = \Civi\Api4\Company::get(FALSE)
      ->setLimit(1)
      ->execute()
      ->first();
  }

  /**
   * A single CSV row should produce a one-line credit note with accounting entries.
   */
  public function testSingleRowCreatesCreditNoteAndLine() {
    $row = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-A',
      'line_unit_price' => 100,
      'line_quantity' => 1,
    ]);

    $result = civicrm_api3('CreditNoteImporter', 'create', $row);
    $this->assertEquals(0, $result['is_error']);
    $creditNoteId = $result['id'];
    $this->assertNotEmpty($creditNoteId);

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();
    $this->assertNotEmpty($creditNote);
    $this->assertEquals(100, $creditNote['subtotal']);
    $this->assertEquals(0, $creditNote['sales_tax']);
    $this->assertEquals(100, $creditNote['total_credit']);
    $this->assertEquals('GBP', $creditNote['currency']);

    $lines = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute();
    $this->assertCount(1, $lines);
    $this->assertEquals(100, $lines->first()['line_total']);
  }

  /**
   * Rows with same credit_note_external_id must end up as two lines on one credit note.
   */
  public function testRowsWithSameExternalIdAreGroupedIntoOneCreditNote() {
    $importerCalls = [
      $this->buildRow([
        'credit_note_external_id' => 'CN-EXT-B',
        'line_unit_price' => 60,
        'line_quantity' => 1,
        'line_description' => 'first line',
      ]),
      $this->buildRow([
        'credit_note_external_id' => 'CN-EXT-B',
        'line_unit_price' => 40,
        'line_quantity' => 1,
        'line_description' => 'second line',
      ]),
    ];

    $createdIds = [];
    foreach ($importerCalls as $row) {
      $createdIds[] = civicrm_api3('CreditNoteImporter', 'create', $row)['id'];
    }

    $this->assertSame($createdIds[0], $createdIds[1], 'Both rows should target the same credit note id');

    $creditNoteId = $createdIds[0];
    $this->assertCreditNoteCount('CN-EXT-B', 1);

    $lines = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->addOrderBy('id', 'ASC')
      ->execute()
      ->getArrayCopy();
    $this->assertCount(2, $lines);
    $this->assertEquals('first line', $lines[0]['description']);
    $this->assertEquals('second line', $lines[1]['description']);

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();
    $this->assertEquals(100, $creditNote['subtotal']);
    $this->assertEquals(100, $creditNote['total_credit']);
  }

  /**
   * Different external ids in the same import must produce separate credit notes.
   */
  public function testDifferentExternalIdsProduceSeparateCreditNotes() {
    $rowA = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-C1',
      'line_unit_price' => 50,
    ]);
    $rowB = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-C2',
      'line_unit_price' => 75,
    ]);

    $idA = civicrm_api3('CreditNoteImporter', 'create', $rowA)['id'];
    $idB = civicrm_api3('CreditNoteImporter', 'create', $rowB)['id'];

    $this->assertNotEquals($idA, $idB);
    $this->assertCreditNoteCount('CN-EXT-C1', 1);
    $this->assertCreditNoteCount('CN-EXT-C2', 1);
  }

  /**
   * The financial transaction should remain a single record per credit
   * note, and its total_amount must equal the negated sum of all the
   * lines that have been appended.
   *
   * Tax is now derived from the financial type's "Sales Tax Account is"
   * relationship rather than supplied by the CSV, so this test uses
   * the default Donation type (no tax) and asserts plain line totals.
   */
  public function testFinancialTrxnTotalsStayConsistentAfterAppending() {
    $creditNoteId = civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-D',
      'line_unit_price' => 100,
      'line_quantity' => 1,
    ]))['id'];

    civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-D',
      'line_unit_price' => 50,
      'line_quantity' => 1,
    ]));

    $entityTrxn = EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->addWhere('entity_id', '=', $creditNoteId)
      ->execute();

    $this->assertCount(1, $entityTrxn);

    $financialTrxn = FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $entityTrxn->first()['financial_trxn_id'])
      ->execute()
      ->first();

    $this->assertEquals(-150, (float) $financialTrxn['total_amount']);

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();
    $this->assertEquals(150, $creditNote['subtotal']);
    $this->assertEquals(0, $creditNote['sales_tax']);
    $this->assertEquals(150, $creditNote['total_credit']);

    $this->assertEquals(-150, (float) $entityTrxn->first()['amount']);
  }

  /**
   * Every line - whether the first or appended - must produce a
   * financial_item linked to the credit note's financial transaction.
   *
   * The financial type used here (Donation) has no Sales Tax account,
   * so the importer derives a 0% tax rate and only the income
   * financial_item is created per line. Tax-bearing financial types
   * are exercised by the upstream APIv4 tests.
   */
  public function testEachLineProducesFinancialItemEntries() {
    $creditNoteId = civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-E',
      'line_unit_price' => 100,
    ]))['id'];

    civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-E',
      'line_unit_price' => 50,
    ]));

    $lines = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->addOrderBy('id', 'ASC')
      ->execute()
      ->getArrayCopy();
    $this->assertCount(2, $lines);

    $line1Items = FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
      ->addWhere('entity_id', '=', $lines[0]['id'])
      ->execute()
      ->getArrayCopy();
    $this->assertCount(1, $line1Items);

    $line2Items = FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNoteLine::$_tableName)
      ->addWhere('entity_id', '=', $lines[1]['id'])
      ->execute()
      ->getArrayCopy();
    $this->assertCount(1, $line2Items);

    foreach (array_merge($line1Items, $line2Items) as $item) {
      $itemTrxn = EntityFinancialTrxn::get(FALSE)
        ->addWhere('entity_table', '=', \CRM_Financial_BAO_FinancialItem::getTableName())
        ->addWhere('entity_id', '=', $item['id'])
        ->execute();
      $this->assertCount(1, $itemTrxn);
    }
  }

  /**
   * The credit note's accounting entries should be correct.
   */
  public function testCreditNoteAccountingEntriesMatchApiV4Path() {
    $creditNoteId = civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-F',
      'line_unit_price' => 80,
    ]))['id'];

    $entityTrxn = EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', \CRM_Financeextras_DAO_CreditNote::$_tableName)
      ->addWhere('entity_id', '=', $creditNoteId)
      ->execute()
      ->first();
    $this->assertNotEmpty($entityTrxn);

    $financialTrxn = FinancialTrxn::get(FALSE)
      ->addWhere('id', '=', $entityTrxn['financial_trxn_id'])
      ->addWhere('status_id:name', '=', 'Pending')
      ->addWhere('payment_instrument_id:name', '=', AccountsReceivablePaymentMethod::NAME)
      ->addWhere('is_payment', '=', FALSE)
      ->execute()
      ->first();
    $this->assertNotEmpty($financialTrxn);
  }

  /**
   * A row that uses contact_external_id rather than contact_id must resolve the customer the same way.
   */
  public function testContactCanBeResolvedByExternalIdentifier() {
    $row = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-G',
      'line_unit_price' => 25,
    ]);
    unset($row['contact_id']);
    $row['contact_external_id'] = 'CUST-EXT-1';

    $result = civicrm_api3('CreditNoteImporter', 'create', $row);
    $this->assertEquals(0, $result['is_error']);

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->execute()
      ->first();
    $this->assertEquals($this->customer['id'], $creditNote['contact_id']);
  }

  /**
   * A missing required field surfaces as an error.
   */
  public function testMissingRequiredFieldReturnsError() {
    $row = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-H',
      'line_unit_price' => 10,
    ]);
    unset($row['line_financial_type']);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/line_financial_type/');

    civicrm_api3('CreditNoteImporter', 'create', $row);
  }

  /**
   * Unknown contact references must produce an actionable error.
   */
  public function testUnknownContactReturnsError() {
    $row = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-I',
      'line_unit_price' => 10,
    ]);
    unset($row['contact_id']);
    $row['contact_external_id'] = 'DOES-NOT-EXIST';

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/Cannot find contact/');

    civicrm_api3('CreditNoteImporter', 'create', $row);
  }

  /**
   * An invalid financial type name must produce an actionable error.
   */
  public function testUnknownFinancialTypeReturnsError() {
    $row = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-J',
      'line_unit_price' => 10,
      'line_financial_type' => 'NotAFinancialType',
    ]);

    $this->expectException(CiviCRM_API3_Exception::class);
    $this->expectExceptionMessageRegExp('/Invalid line financial type/');

    civicrm_api3('CreditNoteImporter', 'create', $row);
  }

  /**
   * Two CSV rows that share a credit_note_external_id but reference a
   * different contact must NOT be merged into the same credit note;
   * the second row should fail with a clear error and leave the
   * existing credit note unchanged.
   */
  public function testRowWithMismatchedContactIsRejected() {
    $otherContact = ContactFabricator::fabricate([
      'first_name' => 'Other',
      'last_name' => 'Customer',
      'external_identifier' => 'CUST-EXT-2',
    ]);

    // First row creates the credit note for CN-EXT-MISMATCH-1 with the
    // default customer.
    $creditNoteId = civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-MISMATCH-1',
      'line_unit_price' => 100,
    ]))['id'];

    // Second row claims the same external id but supplies a different
    // contact - that's a data error, not an append.
    $rejected = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-MISMATCH-1',
      'line_unit_price' => 50,
      'contact_id' => $otherContact['id'],
    ]);

    try {
      civicrm_api3('CreditNoteImporter', 'create', $rejected);
      $this->fail('Expected the mismatched contact row to be rejected.');
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->assertRegExp('/contact id .* does not match/', $e->getMessage());
    }

    // The original credit note must still have only one line.
    $lines = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute();
    $this->assertCount(1, $lines);
  }

  /**
   * Same as above for owner_organization_id - mismatched owner means
   * the row must be rejected.
   */
  public function testRowWithMismatchedOwnerOrgIsRejected() {
    $otherOrg = ContactFabricator::fabricateOrganization([
      'organization_name' => 'Other Owner Co',
    ]);

    $creditNoteId = civicrm_api3('CreditNoteImporter', 'create', $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-MISMATCH-2',
      'line_unit_price' => 100,
    ]))['id'];

    $rejected = $this->buildRow([
      'credit_note_external_id' => 'CN-EXT-MISMATCH-2',
      'line_unit_price' => 50,
      'owner_organization_id' => $otherOrg['id'],
    ]);

    try {
      civicrm_api3('CreditNoteImporter', 'create', $rejected);
      $this->fail('Expected the mismatched owner organisation row to be rejected.');
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->assertRegExp('/owner organisation .* does not match/', $e->getMessage());
    }

    $lines = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $creditNoteId)
      ->execute();
    $this->assertCount(1, $lines);
  }

  /**
   * Asserts the number of credit notes whose external_id custom field
   * matches the supplied value. Queries the custom-field shadow table
   * directly to mirror what the production importer does (APIv4
   * custom-field reads/writes do not reliably persist for
   * extension-defined DAO entities).
   */
  private function assertCreditNoteCount(string $externalId, int $expected): void {
    $count = (int) \CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM civicrm_value_credit_note_ext_id WHERE external_id = %1',
      [1 => [$externalId, 'String']]
    );
    $this->assertEquals($expected, $count, sprintf('Expected %d credit note(s) for external id %s, got %d', $expected, $externalId, $count));
  }

  private function buildRow(array $overrides = []): array {
    return array_merge([
      'credit_note_external_id' => 'CN-EXT-DEFAULT',
      'contact_id' => $this->customer['id'],
      'owner_organization_id' => $this->ownerOrg['contact_id'],
      'currency' => 'GBP',
      'line_unit_price' => 100,
      'line_quantity' => 1,
      'line_financial_type' => 'Donation',
    ], $overrides);
  }

}
