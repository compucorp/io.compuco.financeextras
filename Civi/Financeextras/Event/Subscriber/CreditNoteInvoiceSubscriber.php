<?php

namespace Civi\Financeextras\Event\Subscriber;

use DateTime;
use Civi\Financeextras\Event\CreditNoteMailedEvent;
use Civi\Financeextras\Event\CreditNoteDownloadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreditNoteInvoiceSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      CreditNoteMailedEvent::NAME => 'createMailActivity',
      CreditNoteDownloadedEvent::NAME => 'createDownloadActivity',
    ];
  }

  /**
   * Creates activity for the creditnote invoice download.
   *
   * @param \Civi\Financeextras\Event\CreditNoteDownloadedEvent $e
   *   The registration event. Add new tokens using register().
   */
  public function createDownloadActivity(CreditNoteDownloadedEvent $e) {
    $activityType = 'Downloaded Invoice';
    $subject = 'Downloaded Credit Note PDF';
    $targetContactId = $this->getCreditNoteContactId($e->getCreditNoteId());
    $attachment = $this->storeFile($e->getCreditNoteInvoice()['html'], $e->getCreditNoteInvoice()['format']);
    $this->createActivity([$targetContactId], $activityType, $subject, $attachment);
  }

  /**
   * Creates activity for the creditnote invoice mail.
   *
   * @param \Civi\Financeextras\Event\CreditNoteMailedEvent $e
   *   The registration event. Add new tokens using register().
   */
  public function createMailActivity(CreditNoteMailedEvent $e) {
    $activityType = 'Emailed Invoice';
    $attachment = $this->storeFile($e->getCreditNoteInvoice()['html'], $e->getCreditNoteInvoice()['format']);
    $this->createActivity($e->getMailedContacts(), $activityType, $e->getSubject(), $attachment);
  }

  /**
   * Creates Credit note invoice activity
   *
   * @param array $targetContactIds
   * @param string $type
   * @param string $subject
   * @param array $attachment
   */
  private function createActivity($targetContactIds, $type, $subject, $attachment) {
    $now = (new DateTime())->format('YmdHis');
    $currentUser = \CRM_Core_Session::singleton()->get('userID');
    \Civi\Api4\Activity::create()
      ->addValue('subject', $subject)
      ->addValue('target_contact_id', $targetContactIds)
      ->addValue('source_contact_id', $currentUser)
      ->addValue('activity_type_id:name', $type)
      ->addValue('activity_date_time', $now)
      ->addValue('attachFile_1', [
        'uri' => $attachment,
        'type' => 'application/pdf',
        'location' => $attachment,
        'upload_date' => date('YmdHis'),
      ])
      ->execute();
  }

  /**
   * Persists the file to local disk
   *
   * @param string $content
   *  The file content
   * @param array $format
   *  The file PDF format
   *
   * @return string
   *   File path
   */
  private function storeFile($content, $format) {
    return \CRM_Utils_Mail::appendPDF('credit_note_invoice.pdf', $content, $format)['fullPath'] ?? '';
  }

  /**
   * Returns a credit note contact ID.
   *
   * @param int $id
   *  Credit Note ID
   *
   * @return int
   *   Credit Note contact ID
   */
  private function getCreditNoteContactId($id) {
    return \Civi\Api4\CreditNote::get()
      ->addSelect('contact_id')
      ->addWhere('id', '=', $id)
      ->execute()
      ->first()['contact_id'];
  }

}
