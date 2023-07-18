<?php

namespace Civi\Financeextras\Event\Listener;

use CRM_Contribute_DAO_Contribution;
use Civi\Financeextras\Utils\OptionValueUtils;
use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;

class ContributionPaymentUpdatedListener {

  public static function handle(ContributionPaymentUpdatedEvent $event) {
    self::updateContributionStatus($event->getContributionId());
  }

  private static function updateContributionStatus($contributionId) {
    $status = 'Pending';
    $contribution = \Civi\Api4\Contribution::get()
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->first();

    if (empty($contribution)) {
      return;
    }

    $status = match (TRUE) {
      empty($contribution['paid_amount']) => 'Pending',
      $contribution['total_amount'] == $contribution['paid_amount'] => 'Completed',
      $contribution['total_amount'] > $contribution['paid_amount'] => 'Partially paid',
      $contribution['total_amount'] < $contribution['paid_amount'] => 'Pending refund',
    };

    $newStatusId = OptionValueUtils::getValueForOptionValue('contribution_status', $status);
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $contributionId;
    $contribution->find(TRUE);
    $contribution->contribution_status_id = $newStatusId;
    $contribution->save(FALSE);
  }

}
