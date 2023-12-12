<?php

use Civi\Api4\CreditNote;
use Civi\Token\TokenProcessor;
use Civi\Financeextras\Event\CreditNoteMailedEvent;

/**
 * Handles Credit Note Email Invoice Task.
 */
class CRM_Financeextras_Form_Contribute_CreditNoteEmail extends CRM_Core_Form {
  use CRM_Contact_Form_Task_EmailTrait;

  /**
   * The array that holds all the contact ids.
   *
   * @var array
   */
  private $_contactIds; // phpcs:ignore

  /**
   * Current form context.
   *
   * @var string
   */
  private $_context; // phpcs:ignore

  /**
   * Credit Note ID.
   *
   * @var int
   */
  private $creditNoteId;

  /**
   * {@inheritDoc}
   */
  public function preProcess() {
    $this->setTitle('Email Credit Note');
    $this->creditNoteId = CRM_Utils_Request::retrieveValue('id', 'Positive');

    $this->setContactIDs();
    $this->setIsSearchContext(FALSE);
    $this->traitPreProcess();
  }

  /**
   * Lists available tokens for this form.
   *
   * Presently all tokens are returned.
   *
   * @return array
   *   List of Available tokens
   *
   * @throws \CRM_Core_Exception
   */
  public function listTokens() {
    $tokenProcessor = new TokenProcessor(Civi::dispatcher(), ['schema' => ['contactId'], 'creditNoteId' => $this->creditNoteId]);
    $tokens = $tokenProcessor->listTokens();

    return $tokens;
  }

  /**
   * Submits the form values.
   *
   * This is also accessible for testing.
   *
   * @param array $formValues
   *   Submitted values.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \API_Exception
   */
  public function submit(array $formValues): void {
    $sents = 0;
    $from = $formValues['from_email_address'];
    $text = $this->getSubmittedValue('text_message');
    $html = $this->getSubmittedValue('html_message');
    $from = CRM_Utils_Mail::formatFromAddress($from);

    $cc = $this->getCc();
    $additionalDetails = empty($cc) ? '' : "\ncc : " . $this->getEmailUrlString($this->getCcArray());

    $bcc = $this->getBcc();
    $additionalDetails .= empty($bcc) ? '' : "\nbcc : " . $this->getEmailUrlString($this->getBccArray());

    /** @var \Civi\Financeextras\Service\CreditNoteInvoiceService */
    $creditNoteInvoiceService = \Civi::service('service.credit_note_invoice');
    $creditNoteInvoice = $creditNoteInvoiceService->render($this->creditNoteId);

    foreach ($this->getRowsForEmails() as $values) {
      $mailParams = [];
      $mailParams['messageTemplate'] = [
        'msg_text' => $text,
        'msg_html' => $html,
        'msg_subject' => $this->getSubject(),
      ];
      $mailParams['tokenContext'] = [
        'contactId' => $values['contact_id'],
        'creditNoteId' => $this->creditNoteId,
      ];
      $mailParams['tplParams'] = [];
      $mailParams['from'] = $from;
      $mailParams['toEmail'] = $values['email'];
      $mailParams['cc'] = $cc ?? NULL;
      $mailParams['bcc'] = $bcc ?? NULL;
      $mailParams['attachments'][] = CRM_Utils_Mail::appendPDF('creditnote_invoice.pdf', $creditNoteInvoice['html'], $creditNoteInvoice['format']);
      // Send the mail.
      [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate($mailParams);
      $sents += ($sent ? 1 : 0);
    }

    CRM_Core_Session::setStatus(ts('Credit Note Sent by Email Successfully.'), ts('Message Sent'), 'success');

    Civi::dispatcher()->dispatch(CreditNoteMailedEvent::NAME, new CreditNoteMailedEvent(
      $this->creditNoteId,
      $creditNoteInvoice,
      $subject,
      array_column($this->getRowsForEmails(), 'contact_id'),
      $html . $additionalDetails
    ));
  }

  /**
   * {@inheritDoc}
   */
  public function setContactIDs() { // phpcs:ignore
    $this->_contactIds = $this->getContactIds();
  }

  /**
   * Returns Credit Note Contact ID.
   *
   * @return array
   *   Contact ID as an array
   */
  protected function getContactIds(): array {
    if (isset($this->_contactIds)) {
      return $this->_contactIds;
    }

    $creditNoteId = CRM_Utils_Request::retrieveValue('id', 'Positive');

    $creditNote = CreditNote::get(FALSE)
      ->addWhere('id', '=', $creditNoteId)
      ->execute()
      ->first();

    $this->_contactIds = [$creditNote['contact_id']];

    return $this->_contactIds;
  }

  /**
   * Get the rows for each contactID.
   *
   * @return array
   *   Array if contact IDs.
   */
  protected function getRows(): array {
    $rows = [];
    foreach ($this->_contactIds as $index => $contactID) {
      $rows[] = [
        'contact_id' => $contactID,
        'schema' => ['contactId' => $contactID],
      ];
    }
    return $rows;
  }

}
