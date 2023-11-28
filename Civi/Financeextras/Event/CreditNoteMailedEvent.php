<?php

namespace Civi\Financeextras\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The fe.creditnote.mailed event is dispatched
 * when a credit note invoice is successfully mailed
 * within the system.
 */
class CreditNoteMailedEvent extends Event {
  public const NAME = 'fe.creditnote.mailed';

  public function __construct(
        protected int $creditNoteId,
        protected array $creditNoteInvoice,
        protected string $mailSubject,
        protected array $contactIds,
        protected string $details,
    ) {
  }

  public function getCreditNoteId() {
    return $this->creditNoteId;
  }

  public function getCreditNoteInvoice() {
    return $this->creditNoteInvoice;
  }

  public function getSubject() {
    return $this->mailSubject;
  }

  public function getMailedContacts() {
    return $this->contactIds;
  }

  public function getDetails() {
    return $this->details;
  }

}
