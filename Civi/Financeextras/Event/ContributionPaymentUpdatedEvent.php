<?php

namespace Civi\Financeextras\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The fe.contribution.received_payment event will be dispatched
 * when payment is added/refunded/cacncelled from a contribution
 * and the contribution status needs to be re caluclated.
 */
class ContributionPaymentUpdatedEvent extends Event {
  public const NAME = 'fe.contribution.received_payment';

  public function __construct(protected int $contributionId) {
  }

  public function getContributionId() {
    return $this->contributionId;
  }

}
