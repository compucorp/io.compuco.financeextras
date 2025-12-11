<?php

use Civi\Api4\CreditNote;
use Civi\Api4\CreditNoteLine;
use Civi\Api4\Contribution;
use Civi\Api4\Company;
use Civi\Api4\FinancialType;
use Civi\Api4\FinancialTrxn;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * CreditNote.AllocateOverpayment API Test Case.
 *
 * @group headless
 */
class Civi_Api4_CreditNote_AllocateOverpaymentActionTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * Pre-created overpaid contributions for tests.
   *
   * Created in setUpBeforeClass() before parent::setUp() modifies
   * financial accounts, which would cause Payment.create to fail.
   *
   * @var array
   */
  private static array $overpaidContributions = [];

  /**
   * Pre-created contacts for tests.
   *
   * @var array
   */
  private static array $contacts = [];

  /**
   * Counter for which contribution to use next.
   *
   * @var int
   */
  private static int $contributionIndex = 0;

  /**
   * The overpayment financial type ID.
   *
   * @var int
   */
  private $overpaymentFinancialTypeId;

  /**
   * {@inheritDoc}
   */
  public static function setUpBeforeClass(): void {
    // Create contributions BEFORE parent::setUp() runs to avoid interference
    // with the Payment API. The parent::setUp() modifies financial accounts
    // which can cause "Array to string conversion" errors in Payment.create.

    // Create 10 overpaid contributions (more than enough for all tests).
    for ($i = 0; $i < 10; $i++) {
      $contact = ContactFabricator::fabricate();
      self::$contacts[$i] = $contact;

      // Determine amounts - most use 100/150, one uses 100/175.
      $invoiceAmount = 100;
      $paidAmount = ($i === 9) ? 175 : 150;

      $trxnId = md5(uniqid() . $i);
      $contributionParams = [
        'financial_type_id' => 'Donation',
        'receive_date' => date('Y-m-d'),
        'total_amount' => $invoiceAmount,
        'contact_id' => $contact['id'],
        'payment_instrument_id' => 'Credit Card',
        'trxn_id' => $trxnId,
        'currency' => 'GBP',
        'contribution_status_id' => 2,
      ];

      $contribution = ContributionFabricator::fabricate($contributionParams);

      // Create overpayment by paying more than invoice amount.
      civicrm_api3('Payment', 'create', [
        'contribution_id' => $contribution['id'],
        'total_amount' => $paidAmount,
        'trxn_date' => date('Y-m-d H:i:s'),
        'trxn_id' => $trxnId,
        'is_send_contribution_notification' => FALSE,
      ]);

      // Store with amounts for reference.
      self::$overpaidContributions[$i] = [
        'id' => $contribution['id'],
        'contact_id' => $contact['id'],
        'invoice_amount' => $invoiceAmount,
        'paid_amount' => $paidAmount,
        'overpayment_amount' => $paidAmount - $invoiceAmount,
      ];
    }

    self::$contributionIndex = 0;
  }

  /**
   * {@inheritDoc}
   */
  public function setUp() {
    parent::setUp();

    \Civi::settings()->set('financeextras_enable_overpayments', TRUE);

    $this->overpaymentFinancialTypeId = FinancialType::create(FALSE)
      ->addValue('name', 'Overpayment Test')
      ->addValue('is_active', TRUE)
      ->execute()
      ->first()['id'];

    $company = Company::get(FALSE)
      ->setLimit(1)
      ->execute()
      ->first();

    Company::update(FALSE)
      ->addWhere('id', '=', $company['id'])
      ->addValue('overpayment_financial_type_id', $this->overpaymentFinancialTypeId)
      ->execute();
  }

  /**
   * Get the next available overpaid contribution.
   *
   * @return array
   *   The contribution data with id, contact_id, invoice_amount,
   *   paid_amount, overpayment_amount.
   */
  private function getNextOverpaidContribution(): array {
    $contribution = self::$overpaidContributions[self::$contributionIndex];
    self::$contributionIndex++;
    return $contribution;
  }

  /**
   * Get the contribution with larger overpayment (75 instead of 50).
   *
   * @return array
   *   The contribution data.
   */
  private function getLargerOverpaymentContribution(): array {
    // Index 9 has 100/175 = 75 overpayment.
    return self::$overpaidContributions[9];
  }

  /**
   * Test allocate overpayment creates credit note with correct data.
   */
  public function testAllocateOverpaymentCreatesCreditNote() {
    $contribution = $this->getNextOverpaidContribution();

    $result = CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute()
      ->first();

    $this->assertNotEmpty($result['id']);
    $this->assertEquals($contribution['contact_id'], $result['contact_id']);
    $this->assertEquals('GBP', $result['currency']);

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->addSelect('*', 'status_id:name')
      ->execute()
      ->first();

    $this->assertEquals('open', $creditNote['status_id:name']);
  }

  /**
   * Test allocate overpayment creates line item with correct data.
   */
  public function testAllocateOverpaymentCreatesCorrectLineItem() {
    $contribution = $this->getNextOverpaidContribution();
    $overpaymentAmount = $contribution['overpayment_amount'];

    $result = CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute()
      ->first();

    $lineItems = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $result['id'])
      ->execute();

    $this->assertCount(1, $lineItems);

    $lineItem = $lineItems->first();
    $this->assertEquals($this->overpaymentFinancialTypeId, $lineItem['financial_type_id']);
    $this->assertEquals(1, $lineItem['quantity']);
    $this->assertEquals($overpaymentAmount, $lineItem['unit_price']);
    $this->assertEquals($overpaymentAmount, $lineItem['line_total']);
    $this->assertEquals(0, $lineItem['tax_amount']);
    $this->assertNotFalse(strpos($lineItem['description'], 'Overpayment'));
  }

  /**
   * Test allocate overpayment records negative payment on contribution.
   */
  public function testAllocateOverpaymentRecordsNegativePayment() {
    $contribution = $this->getNextOverpaidContribution();
    $overpaymentAmount = $contribution['overpayment_amount'];

    $result = CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute()
      ->first();

    $payments = FinancialTrxn::get(FALSE)
      ->addWhere('trxn_id', '=', $result['cn_number'])
      ->execute();

    $this->assertCount(1, $payments);

    $payment = $payments->first();
    $this->assertEquals(-$overpaymentAmount, $payment['total_amount']);
  }

  /**
   * Test allocate overpayment changes contribution status to completed.
   */
  public function testAllocateOverpaymentChangesContributionStatusToCompleted() {
    $contribution = $this->getNextOverpaidContribution();

    $initialContribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contribution['id'])
      ->addSelect('contribution_status_id:name')
      ->execute()
      ->first();
    $this->assertEquals('Pending refund', $initialContribution['contribution_status_id:name']);

    CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute();

    $updatedContribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contribution['id'])
      ->addSelect('contribution_status_id:name')
      ->execute()
      ->first();

    $this->assertEquals('Completed', $updatedContribution['contribution_status_id:name']);
  }

  /**
   * Test allocate overpayment payment status is completed not refunded.
   */
  public function testAllocateOverpaymentPaymentStatusIsCompleted() {
    $contribution = $this->getNextOverpaidContribution();

    $result = CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute()
      ->first();

    $payment = FinancialTrxn::get(FALSE)
      ->addWhere('trxn_id', '=', $result['cn_number'])
      ->addSelect('*', 'status_id:name')
      ->execute()
      ->first();

    $this->assertEquals('Completed', $payment['status_id:name']);
  }

  /**
   * Test allocate overpayment fails when setting is disabled.
   */
  public function testAllocateOverpaymentFailsWhenSettingDisabled() {
    \Civi::settings()->set('financeextras_enable_overpayments', FALSE);

    $contribution = $this->getNextOverpaidContribution();

    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('not eligible');

    CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute();
  }

  /**
   * Test allocate overpayment fails for non-overpaid contribution.
   */
  public function testAllocateOverpaymentFailsForNonOverpaidContribution() {
    $contact = ContactFabricator::fabricate();

    // Create a non-overpaid contribution using API4 (no Payment.create needed).
    $contribution = Contribution::create(FALSE)
      ->addValue('contact_id', $contact['id'])
      ->addValue('financial_type_id', 1)
      ->addValue('total_amount', 100)
      ->addValue('contribution_status_id:name', 'Completed')
      ->addValue('currency', 'GBP')
      ->addValue('receive_date', date('Y-m-d'))
      ->execute()
      ->first();

    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('not eligible');

    CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute();
  }

  /**
   * Test allocate overpayment fails without contribution ID.
   */
  public function testAllocateOverpaymentFailsWithoutContributionId() {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('Contribution ID is required');

    CreditNote::allocateOverpayment(FALSE)
      ->execute();
  }

  /**
   * Test allocate overpayment fails when overpayment financial type not configured.
   */
  public function testAllocateOverpaymentFailsWhenFinancialTypeNotConfigured() {
    $company = Company::get(FALSE)
      ->setLimit(1)
      ->execute()
      ->first();

    Company::update(FALSE)
      ->addWhere('id', '=', $company['id'])
      ->addValue('overpayment_financial_type_id', NULL)
      ->execute();

    $contribution = $this->getNextOverpaidContribution();

    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage('financial type is not configured');

    CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute();
  }

  /**
   * Test credit note remains open with full credit available.
   */
  public function testCreditNoteRemainsOpenWithFullCredit() {
    $contribution = $this->getLargerOverpaymentContribution();
    $overpaymentAmount = $contribution['overpayment_amount'];

    $result = CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute()
      ->first();

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->addSelect('*', 'status_id:name')
      ->execute()
      ->first();

    $this->assertEquals('open', $creditNote['status_id:name']);
    $this->assertEquals($overpaymentAmount, $creditNote['total_credit']);

    $allocations = \Civi\Api4\CreditNoteAllocation::get(FALSE)
      ->addWhere('credit_note_id', '=', $result['id'])
      ->execute();

    $this->assertCount(0, $allocations);
  }

  /**
   * Test allocate overpayment does NOT add tax even when financial type has tax rate.
   *
   * The overpayment amount is already the gross cash difference (total_payments
   * minus contribution_total), where contribution_total is tax-inclusive.
   * Adding tax would inflate the credit note beyond the actual overpayment.
   */
  public function testAllocateOverpaymentDoesNotAddTaxEvenWhenConfigured() {
    $contribution = $this->getNextOverpaidContribution();
    $overpaymentAmount = $contribution['overpayment_amount'];
    $taxRate = 20;

    // Create a financial account with tax rate.
    $taxAccount = \Civi\Api4\FinancialAccount::create(FALSE)
      ->addValue('name', 'Test Sales Tax Account')
      ->addValue('financial_account_type_id:name', 'Liability')
      ->addValue('is_tax', TRUE)
      ->addValue('tax_rate', $taxRate)
      ->addValue('is_active', TRUE)
      ->execute()
      ->first();

    // Link tax account to the overpayment financial type.
    $accountRelationshipId = \CRM_Core_PseudoConstant::getKey(
      'CRM_Financial_DAO_EntityFinancialAccount',
      'account_relationship',
      'Sales Tax Account is'
    );

    \Civi\Api4\EntityFinancialAccount::create(FALSE)
      ->addValue('entity_table', 'civicrm_financial_type')
      ->addValue('entity_id', $this->overpaymentFinancialTypeId)
      ->addValue('account_relationship', $accountRelationshipId)
      ->addValue('financial_account_id', $taxAccount['id'])
      ->execute();

    $result = CreditNote::allocateOverpayment(FALSE)
      ->setContributionId($contribution['id'])
      ->execute()
      ->first();

    $lineItems = CreditNoteLine::get(FALSE)
      ->addWhere('credit_note_id', '=', $result['id'])
      ->execute();

    $this->assertCount(1, $lineItems);

    $lineItem = $lineItems->first();
    // Tax should be zero - overpayment is already gross cash.
    $this->assertEquals(0, $lineItem['tax_amount']);
    $this->assertEquals($overpaymentAmount, $lineItem['line_total']);

    // Total credit should equal overpayment amount (no tax added).
    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $result['id'])
      ->execute()
      ->first();
    $this->assertEquals($overpaymentAmount, $creditNote['total_credit']);
  }

}
