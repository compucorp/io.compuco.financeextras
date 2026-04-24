<?php

namespace Civi\Financeextras\Hook\Merge;

use BaseHeadlessTest;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\MembershipFabricator;
use Civi\Financeextras\Test\Fabricator\MembershipTypeFabricator;

/**
 * Tests for Civi\Financeextras\Hook\Merge\ContactMerge.
 *
 * @group headless
 */
class ContactMergeTest extends BaseHeadlessTest {

  public function testShouldHandleReturnsFalseForNonSqlsType() {
    $this->assertFalse(ContactMerge::shouldHandle('cidRefs', 1, 2));
    $this->assertFalse(ContactMerge::shouldHandle('eidRefs', 1, 2));
    $this->assertFalse(ContactMerge::shouldHandle('relTables', 1, 2));
  }

  public function testShouldHandleReturnsFalseWhenEitherIdIsMissing() {
    $this->assertFalse(ContactMerge::shouldHandle('sqls', NULL, 2));
    $this->assertFalse(ContactMerge::shouldHandle('sqls', 1, NULL));
    $this->assertFalse(ContactMerge::shouldHandle('sqls', 0, 2));
    $this->assertFalse(ContactMerge::shouldHandle('sqls', 1, 0));
  }

  public function testShouldHandleReturnsTrueForSqlsTypeWithBothIds() {
    $this->assertTrue(ContactMerge::shouldHandle('sqls', 1, 2));
  }

  public function testHandleAppendsRestoreSqlForEventContributionPaidByOrganisation() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();
    $employer = ContactFabricator::fabricateOrganization();

    [$contributionId] = $this->createEventContributionLinkedToParticipant(
      $otherContact['id'],
      $employer['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertTrue(
      in_array(
        "UPDATE civicrm_contribution SET contact_id = {$employer['id']} WHERE id = {$contributionId}",
        $sqls,
        TRUE
      ),
      'Expected restore UPDATE for organisation-paid event contribution to be appended to $sqls.'
    );
  }

  public function testHandleDoesNotAppendRestoreSqlForSelfPaidEventContribution() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();

    $this->createEventContributionLinkedToParticipant(
      $otherContact['id'],
      $otherContact['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertEmpty(
      $sqls,
      'Self-paid event contribution must not trigger a restore UPDATE.'
    );
  }

  public function testHandleDoesNotAppendRestoreSqlForEventContributionPaidByAnotherIndividual() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();
    $thirdPartyIndividual = ContactFabricator::fabricate();

    // Payer is an Individual (not an Organisation), so the employer-
    // preservation rule does NOT apply and nothing should be appended.
    $this->createEventContributionLinkedToParticipant(
      $otherContact['id'],
      $thirdPartyIndividual['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertEmpty(
      $sqls,
      'Event contribution paid by a non-Organisation must not trigger a restore UPDATE.'
    );
  }

  public function testHandleAppendsRestoreSqlForMembershipContributionPaidByOrganisation() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();
    $employer = ContactFabricator::fabricateOrganization();

    [$contributionId] = $this->createMembershipContributionLinkedToMembership(
      $otherContact['id'],
      $employer['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertTrue(
      in_array(
        "UPDATE civicrm_contribution SET contact_id = {$employer['id']} WHERE id = {$contributionId}",
        $sqls,
        TRUE
      ),
      'Expected restore UPDATE for organisation-paid membership contribution to be appended to $sqls.'
    );
  }

  public function testHandleDoesNotAppendRestoreSqlForSelfPaidMembershipContribution() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();

    $this->createMembershipContributionLinkedToMembership(
      $otherContact['id'],
      $otherContact['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertEmpty(
      $sqls,
      'Self-paid membership contribution must not trigger a restore UPDATE.'
    );
  }

  public function testHandleDoesNotAppendRestoreSqlForMembershipContributionPaidByAnotherIndividual() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();
    $thirdPartyIndividual = ContactFabricator::fabricate();

    // Payer is an Individual (not an Organisation), so the employer-
    // preservation rule does NOT apply and nothing should be appended.
    $this->createMembershipContributionLinkedToMembership(
      $otherContact['id'],
      $thirdPartyIndividual['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertEmpty(
      $sqls,
      'Membership contribution paid by a non-Organisation must not trigger a restore UPDATE.'
    );
  }

  public function testHandleAppendsRestoreSqlsForBothEventAndMembershipContributionsInSameMerge() {
    $mainContact = ContactFabricator::fabricate();
    $otherContact = ContactFabricator::fabricate();
    $employer = ContactFabricator::fabricateOrganization();

    [$eventContributionId] = $this->createEventContributionLinkedToParticipant(
      $otherContact['id'],
      $employer['id']
    );
    [$membershipContributionId] = $this->createMembershipContributionLinkedToMembership(
      $otherContact['id'],
      $employer['id']
    );

    $sqls = $this->runHandle($mainContact['id'], $otherContact['id']);

    $this->assertTrue(
      in_array(
        "UPDATE civicrm_contribution SET contact_id = {$employer['id']} WHERE id = {$eventContributionId}",
        $sqls,
        TRUE
      ),
      'Event contribution restore UPDATE missing from $sqls in combined merge scenario.'
    );
    $this->assertTrue(
      in_array(
        "UPDATE civicrm_contribution SET contact_id = {$employer['id']} WHERE id = {$membershipContributionId}",
        $sqls,
        TRUE
      ),
      'Membership contribution restore UPDATE missing from $sqls in combined merge scenario.'
    );
  }

  public function testHandleDoesNotAppendRestoreSqlWhenOrganisationBeingMergedIsItsOwnPayer() {
    // Edge case: CiviCRM allows merging two Organisations. If the "other"
    // organisation paid for its own participant/membership record,
    // contribution.contact_id equals $otherId and the contact_type IS
    // 'Organization' – so the Organisation filter alone would match it.
    // The explicit contact_id <> %1 guard must exclude this row so that
    // core's behaviour (move the contribution to the surviving org) is
    // preserved.
    $mainOrganisation = ContactFabricator::fabricateOrganization();
    $otherOrganisation = ContactFabricator::fabricateOrganization();

    $this->createEventContributionLinkedToParticipant(
      $otherOrganisation['id'],
      $otherOrganisation['id']
    );

    $sqls = $this->runHandle($mainOrganisation['id'], $otherOrganisation['id']);

    $this->assertEmpty(
      $sqls,
      'Contribution whose payer is the organisation being merged away must not be restored.'
    );
  }

  /**
   * Instantiates the hook with a fresh $sqls array and returns it after
   * handle() has run.
   */
  private function runHandle(int $mainId, int $otherId): array {
    $sqls = [];
    (new ContactMerge('sqls', $sqls, $mainId, $otherId))->handle();
    return $sqls;
  }

  /**
   * @return array [contributionId, participantId]
   */
  private function createEventContributionLinkedToParticipant(int $participantContactId, int $payerContactId): array {
    $eventId = civicrm_api3('Event', 'create', [
      'title'         => 'Merge Test Event ' . uniqid(),
      'event_type_id' => 1,
      'start_date'    => date('Y-m-d'),
      'is_active'     => 1,
    ])['id'];

    $participantId = civicrm_api3('Participant', 'create', [
      'event_id'      => $eventId,
      'contact_id'    => $participantContactId,
      'status_id'     => 'Registered',
      'role_id'       => 'Attendee',
      'register_date' => date('Y-m-d H:i:s'),
    ])['id'];

    $contributionId = $this->insertContribution($payerContactId, 'Event Fee');

    \CRM_Core_DAO::executeQuery(
      'INSERT INTO civicrm_participant_payment (contribution_id, participant_id) VALUES (%1, %2)',
      [
        1 => [$contributionId, 'Integer'],
        2 => [$participantId, 'Integer'],
      ]
    );

    return [$contributionId, $participantId];
  }

  /**
   * @return array [contributionId, membershipId]
   */
  private function createMembershipContributionLinkedToMembership(int $membershipContactId, int $payerContactId): array {
    $membershipType = MembershipTypeFabricator::fabricate([
      'name'        => 'Merge Test Membership ' . uniqid(),
      'minimum_fee' => 100,
    ]);

    $membership = MembershipFabricator::fabricate([
      'contact_id'         => $membershipContactId,
      'membership_type_id' => $membershipType['id'],
      'join_date'          => date('Y-m-d'),
      'start_date'         => date('Y-m-d'),
      'financial_type_id'  => 'Member Dues',
      'skipLineItem'       => 1,
    ]);

    $contributionId = $this->insertContribution($payerContactId, 'Member Dues');

    \CRM_Core_DAO::executeQuery(
      'INSERT INTO civicrm_membership_payment (contribution_id, membership_id) VALUES (%1, %2)',
      [
        1 => [$contributionId, 'Integer'],
        2 => [$membership['id'], 'Integer'],
      ]
    );

    return [$contributionId, $membership['id']];
  }

  /**
   * @retunr int
   */
  private function insertContribution(int $contactId, string $financialTypeName): int {
    $statuses = array_flip(
      \CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate')
    );
    $financialTypeId = (int) civicrm_api3('FinancialType', 'getvalue', [
      'name'   => $financialTypeName,
      'return' => 'id',
    ]);

    \CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_contribution
         (contact_id, financial_type_id, receive_date, total_amount, payment_instrument_id, trxn_id, currency, contribution_status_id)
       VALUES
         (%1, %2, %3, 100, 1, %4, 'GBP', %5)",
      [
        1 => [$contactId, 'Integer'],
        2 => [$financialTypeId, 'Integer'],
        3 => [date('Y-m-d'), 'String'],
        4 => [md5(uniqid('', TRUE)), 'String'],
        5 => [$statuses['Completed'], 'Integer'],
      ]
    );

    return (int) \CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
  }

}
