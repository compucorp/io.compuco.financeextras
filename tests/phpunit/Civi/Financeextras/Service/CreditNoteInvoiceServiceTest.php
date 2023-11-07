<?php

namespace Civi\Financeextras\Service;

use BaseHeadlessTest;
use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\CreditNote;
use Civi\Financeextras\Test\Helper\CreditNoteTrait;
use Civi\Financeextras\WorkflowMessage\CreditNoteInvoice;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;

/**
 * @group headless
 */
class CreditNoteInvoiceServiceTest extends BaseHeadlessTest {

  use CreditNoteTrait;

  /**
   * Asserts invoice will render expected credit note tokens & tplParams.
   */
  public function testCreditNoteInvoiceDataRendersAsExpected() {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $lineItem1 = $this->getCreditNoteLineData();
    $creditNote['items'][] = $lineItem2 = $this->getCreditNoteLineData();

    $creditNote['total_credit'] = CreditNoteBAO::computeTotalAmount($creditNote['items'])['totalAfterTax'];

    $creditNote = (object) (CreditNote::save(FALSE)
      ->addRecord($creditNote)
      ->execute()
      ->first());

    $this->createAddressForContact($creditNote->contact_id);

    $contact = (object) (Contact::get(FALSE)
      ->addWhere('id', '=', $creditNote->contact_id)
      ->execute()
      ->first());

    $ownerOrg = (object) (Contact::get(FALSE)
      ->addWhere('id', '=', $creditNote->owner_organization)
      ->execute()
      ->first());

    $invoiceService = new CreditNoteInvoiceService(new CreditNoteInvoice());
    $invoice = $invoiceService->render($creditNote->id);

    $subtotal = \CRM_Utils_Money::format($creditNote->subtotal, $creditNote->currency);
    $totalCredit = \CRM_Utils_Money::format($creditNote->total_credit, $creditNote->currency);
    $this->assertArrayHasKey("html", $invoice);
    $this->assertRegExp('/' . $contact->display_name . '/', $invoice['html']);
    $this->assertRegExp('/' . $ownerOrg->organization_name . '/', $invoice['html']);
    $this->assertRegExp('/Supplementary Address 1/', $invoice['html']);
    $this->assertRegExp('/Supplementary Address 2/', $invoice['html']);
    $this->assertRegExp('/' . str_replace(' ', '', $subtotal) . '/', $invoice['html']);
    $this->assertRegExp('/' . str_replace(' ', '', $totalCredit) . '/', $invoice['html']);
    $this->assertRegExp('/' . $lineItem1['description'] . '/', $invoice['html']);
    $this->assertRegExp('/' . $lineItem2['description'] . '/', $invoice['html']);
    $this->assertRegExp('/' . $lineItem1['quantity'] . '/', $invoice['html']);
    $this->assertRegExp('/' . $lineItem2['quantity'] . '/', $invoice['html']);
  }

  private function createAddressForContact($contactId) {
    Address::save(FALSE)
      ->addRecord([
        "contact_id" => $contactId,
        "location_type_id" => 5,
        "is_primary" => TRUE,
        "is_billing" => TRUE,
        "street_address" => "Coldharbour Ln",
        "street_number" => "42",
        "supplemental_address_1" => "Supplementary Address 1",
        "supplemental_address_2" => "Supplementary Address 2",
        "supplemental_address_3" => "Supplementary Address 3",
        "city" => "Hayes",
        "postal_code" => "UB3 3EA",
        "country_id" => 1226,
        "manual_geo_code" => FALSE,
        "timezone" => NULL,
        "name" => NULL,
        "master_id" => NULL,
      ])
      ->execute()
      ->first();
  }

}
