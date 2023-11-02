<?php

namespace Civi\Financeextras\Setup\Configure;

/**
 * For this extension to work properly we need
 * at least one company. This adds a company
 * with sane defaults which can be altered by the admin
 * afterward.
 * It also sets this default company in a way that
 * allows sites to migrate gracefully from Civi core
 * sequential numbering (for both invoices and
 * credit notes) to the improved sequential numbering
 * and credit note functionality that is provided by
 * this extension.
 */
class SetDefaultCompany implements ConfigurerInterface {

  public function apply() {
    try {
      \Civi\Api4\Company::create(FALSE)
        ->addValue('contact_id', $this->getDefaultDomainContact())
        ->addValue('invoice_template_id:name', 'Contributions - Invoice')
        ->addValue('invoice_prefix', $this->getDefaultInvoicePrefix())
        ->addValue('next_invoice_number', $this->getDefaultNextInvoiceNumber())
        ->addValue('creditnote_template_id:name', 'Credit Note Invoice')
        ->addValue('next_creditnote_number', $this->getDefaultNextCreditNoteNumber())
        ->addValue('creditnote_prefix', $this->getDefaultCreditNotePrefix())
        ->execute();
    }
    catch (\Exception $exception) {
    }
  }

  private function getDefaultDomainContact() {
    return \CRM_Core_BAO_Domain::getDomain()->contact_id;
  }

  /**
   * Sets the default company invoice prefix
   * to the current one that is configured in Civi
   * core, but if there is no prefix configured then
   * we just default it to "SI".
   *
   * @return string|null
   */
  private function getDefaultInvoicePrefix() {
    if (\Civi::settings()->get('invoicing')) {
      return \Civi::settings()->get('invoice_prefix') ?? 'SI';
    }

    return 'SI';
  }

  /**
   * Sets the default company credit note prefix
   * to the current one that is configured in Civi
   * core, but if there is no prefix configured then
   * we just default it to "CN".
   *
   * @return string|null
   */
  private function getDefaultCreditNotePrefix() {
    return \Civi::settings()->get('credit_notes_prefix') ?? 'CN';
  }

  /**
   * If invoicing is enabled, then it calculates the
   * next invoice number using the way Civi core calculates
   * invoice numbers, which is basically getting the
   * max contribution id + 1, or to 1 if there is no
   * contribution exist on the system.
   *
   * If invoicing is disabled then we just default
   * the sequential numbering to start from 1.
   *
   * @return string
   */
  private function getDefaultNextInvoiceNumber() {
    if (\Civi::settings()->get('invoicing')) {
      return \CRM_Core_DAO::singleValueQuery('SELECT COALESCE(MAX(id) + 1, 1) FROM civicrm_contribution');
    }

    return '1';
  }

  /**
   * Calculates the next credit number using the way Civi core
   * does it, which is counting the number of all available
   * credit notes + 1, then checking if any existing credit
   * note has the same number,and if so then it keeps
   * incrementing it by one until we have a credit note number
   * that does not exsiting on the system, which will be the
   * next credit note number.
   *
   * On new sites there will be no credit notes, so we just
   * default it to '1'.
   *
   * @return string
   */
  private function getDefaultNextCreditNoteNumber() {
    $creditNoteNum = \CRM_Core_DAO::singleValueQuery('SELECT count(creditnote_id) as creditnote_number FROM civicrm_contribution WHERE creditnote_id IS NOT NULL');

    do {
      $creditNoteNum++;
      $creditNoteId = \Civi::settings()->get('credit_notes_prefix') . '' . $creditNoteNum;
      $result = civicrm_api3('Contribution', 'getcount', [
        'sequential' => 1,
        'creditnote_id' => $creditNoteId,
      ]);
    } while ($result > 0);

    return $creditNoteNum ?? '1';
  }

}
