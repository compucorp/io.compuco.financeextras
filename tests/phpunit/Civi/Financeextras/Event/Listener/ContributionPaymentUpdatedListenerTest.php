<?php

use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * Tests for ContributionPaymentUpdatedListener.
 *
 * @group headless
 */
class ContributionPaymentUpdatedListenerTest extends BaseHeadlessTest {

  /**
   * @var array
   */
  private array $contact;

  public function setUp(): void {
    parent::setUp();
    $this->contact = ContactFabricator::fabricate();
  }

  public function testZeroAmountContributionCreatedAsCompletedStaysCompleted(): void {
    $contribution = $this->createContribution(0, 'Completed');

    $this->assertEquals('Completed', $this->getContributionStatus($contribution['id']));
  }

  public function testZeroAmountContributionCreatedAsPendingIsUpdatedToCompleted(): void {
    $contribution = $this->createContribution(0, 'Pending');

    $this->assertEquals('Completed', $this->getContributionStatus($contribution['id']));
  }

  public function testContributionWithNoPaymentIsPending(): void {
    $contribution = $this->createContribution(100, 'Pending');

    $this->dispatchPaymentUpdatedEvent($contribution['id']);

    $this->assertEquals('Pending', $this->getContributionStatus($contribution['id']));
  }

  public function testContributionWithPartialPaymentIsPartiallyPaid(): void {
    $contribution = $this->createContribution(100, 'Pending');
    $this->createPayment($contribution['id'], 40);

    $this->dispatchPaymentUpdatedEvent($contribution['id']);

    $this->assertEquals('Partially paid', $this->getContributionStatus($contribution['id']));
  }

  public function testContributionWithFullPaymentIsCompleted(): void {
    $contribution = $this->createContribution(100, 'Pending');
    $this->createPayment($contribution['id'], 100);

    $this->dispatchPaymentUpdatedEvent($contribution['id']);

    $this->assertEquals('Completed', $this->getContributionStatus($contribution['id']));
  }

  public function testContributionPaidMoreThanTotalIsPendingRefund(): void {
    $contribution = $this->createContribution(100, 'Pending');
    $this->createPayment($contribution['id'], 100);
    // Lower the total below the amount already paid to simulate an overpayment.
    CRM_Core_DAO::executeQuery(
      'UPDATE civicrm_contribution SET total_amount = 50 WHERE id = %1',
      [1 => [$contribution['id'], 'Positive']]
    );

    $this->dispatchPaymentUpdatedEvent($contribution['id']);

    $this->assertEquals('Pending refund', $this->getContributionStatus($contribution['id']));
  }

  private function createContribution(float $amount, string $status): array {
    return ContributionFabricator::fabricate([
      'financial_type_id' => 'Event Fee',
      'receive_date' => date('Y-m-d'),
      'total_amount' => $amount,
      'contact_id' => $this->contact['id'],
      'payment_instrument_id' => 'Credit Card',
      'currency' => 'GBP',
      'contribution_status_id' => $status,
    ]);
  }

  private function createPayment(int $contributionId, float $amount): void {
    civicrm_api3('Payment', 'create', [
      'contribution_id' => $contributionId,
      'total_amount' => $amount,
      'trxn_date' => date('Y-m-d H:i:s'),
      'trxn_id' => md5(uniqid('', TRUE)),
      'is_send_contribution_notification' => FALSE,
    ]);
  }

  private function dispatchPaymentUpdatedEvent(int $contributionId): void {
    \Civi::dispatcher()->dispatch(
      ContributionPaymentUpdatedEvent::NAME,
      new ContributionPaymentUpdatedEvent($contributionId)
    );
  }

  private function getContributionStatus(int $contributionId): string {
    return \Civi\Api4\Contribution::get(FALSE)
      ->addSelect('contribution_status_id:name')
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->first()['contribution_status_id:name'];
  }

}
