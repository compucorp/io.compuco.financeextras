<?php

use Civi\Financeextras\Event\CreditNoteDownloadedEvent;

/**
 * Handles Credit Note Download Task.
 */
class CRM_Financeextras_Form_Contribute_CreditNoteDownload extends CRM_Core_Form {

  /**
   * Renders and return the generated PDF to the browser.
   */
  public static function download(): void {
    $creditNoteId = CRM_Utils_Request::retrieveValue('id', 'Positive');
    /** @var \Civi\Financeextras\Service\CreditNoteInvoiceService */
    $creditNoteInvoiceService = \Civi::service('service.credit_note_invoice');
    $rendered = $creditNoteInvoiceService->render($creditNoteId);
    ob_end_clean();
    CRM_Utils_PDF_Utils::html2pdf($rendered['html'], 'credit_note_invoice.pdf', FALSE, $rendered['format']);

    Civi::dispatcher()->dispatch(CreditNoteDownloadedEvent::NAME, new CreditNoteDownloadedEvent(
      $creditNoteId,
      $rendered
    ));

    CRM_Utils_System::civiExit();
  }

}
