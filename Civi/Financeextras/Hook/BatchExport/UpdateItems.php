<?php

namespace Civi\Financeextras\Hook\BatchExport;

/**
 * Updates the generated items of CSV batch export
 */
class UpdateItems {

  public function __construct(public array &$results, public array &$items) {}

  public function addCreditNoteNumberAsInvoiceNumber() {
    $creditInvoiceNumber = [];
    foreach ($this->results as $result) {
      if (!empty($result['credit_note_number'])) {
        $creditInvoiceNumber[$result['financial_trxn_id']] = $result['credit_note_number'];
      }
    }

    foreach ($this->items as &$item) {
      if (!empty($creditInvoiceNumber[$item['Financial Trxn ID/Internal ID']])) {
        $item['Invoice No'] = $creditInvoiceNumber[$item['Financial Trxn ID/Internal ID']];
      }
    }
  }

}
