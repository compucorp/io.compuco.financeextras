<?php

namespace Civi\Financeextras\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The fe.creditnote.downloaded event is dispatched
 * when a credit note invoice is successfully downloaded
 * within the system.
 */
class CreditNoteDownloadedEvent extends Event {
  public const NAME = 'fe.creditnote.downloaded';

  public function __construct(
        protected int $creditNoteId,
        protected array $creditNoteInvoice
    ) {
  }

  public function getCreditNoteId() {
    return $this->creditNoteId;
  }

  public function getCreditNoteInvoice() {
    return $this->creditNoteInvoice;
  }

}
