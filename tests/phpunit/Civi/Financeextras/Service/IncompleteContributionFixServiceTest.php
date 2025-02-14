<?php

use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Service\IncompleteContributionFixService;
use Civi\Financeextras\Test\Fabricator\MembershipTypeFabricator;
use Civi\Financeextras\Test\Fabricator\MembershipFabricator;

/**
 * Tests for the Refund class.
 *
 * @group headless
 */
class IncompleteContributionFixServiceTest extends BaseHeadlessTest {

  public function testContributionWithoutLineItemsGetsFixed() {
    $today = date('Y-m-d');
    $trxnId = md5(time());
    $contributionStatuses = array_flip(\CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate'));

    $contact = ContactFabricator::fabricate();
    $membershiptType = MembershipTypeFabricator::fabricate([
      'name' => 'Main Membership',
      'minimum_fee' => 200,
    ]);
    $membership = MembershipFabricator::fabricate([
      'contact_id' => $contact['id'],
      'membership_type_id' => $membershiptType['id'],
      'join_date' => $today,
      'start_date' => $today,
      'financial_type_id' => 'Member Dues',
      'skipLineItem' => 1,
    ]);

    $createContributionQuery = "
    INSERT INTO civicrm_contribution
    (contact_id, financial_type_id, receive_date, total_amount, payment_instrument_id, trxn_id, currency, contribution_status_id)
    VALUES
    ({$contact['id']}, 1, '{$today}', 200, 1, '{$trxnId}', 'GBP', {$contributionStatuses['Pending']});";
    \CRM_Core_DAO::executeQuery($createContributionQuery);
    $contributionId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');

    $createMembershipPaymentQuery = "
    INSERT INTO civicrm_membership_payment
    (contribution_id, membership_id)
    VALUES
    ({$contributionId}, {$membership['id']});";
    \CRM_Core_DAO::executeQuery($createMembershipPaymentQuery);

    $invalidContributionsQuery = "
    SELECT count(cc.id) from civicrm_contribution cc
    LEFT JOIN civicrm_line_item li ON cc.id = li.contribution_id
    WHERE li.id IS NULL AND cc.contribution_status_id IN ({$contributionStatuses['Pending']}, {$contributionStatuses['Completed']})
    AND cc.is_pay_later = 0";
    $invalidContributionsCount = \CRM_Core_DAO::singleValueQuery($invalidContributionsQuery);
    $this->assertEquals(1, $invalidContributionsCount);

    $service = new IncompleteContributionFixService();
    $service->execute();

    $invalidContributionsCount = \CRM_Core_DAO::singleValueQuery($invalidContributionsQuery);

    $this->assertEquals(0, $invalidContributionsCount);
    $this->assertEquals(200, (int) \CRM_Core_BAO_FinancialTrxn::getTotalPayments($contributionId, TRUE));

    $contributionStatus = \CRM_Core_DAO::singleValueQuery("SELECT contribution_status_id FROM civicrm_contribution WHERE id = {$contributionId}");
    $this->assertEquals($contributionStatuses['Completed'], (int) $contributionStatus);
  }

}
