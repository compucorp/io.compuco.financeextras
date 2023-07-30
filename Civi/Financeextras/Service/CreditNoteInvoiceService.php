<?php

namespace Civi\Financeextras\Service;

use CRM_Utils_Array;
use CRM_Core_Config;
use Civi\Api4\Contact;
use CRM_Core_BAO_Domain;
use Civi\Api4\CreditNote;
use Civi\Api4\Contribution;
use Civi\Api4\CreditNoteLine;
use Civi\Api4\CreditNoteAllocation;
use Civi\Api4\Setting;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;
use Civi\Financeextras\WorkflowMessage\CreditNoteInvoice;

class CreditNoteInvoiceService {

  /**
   * CreditNoteInvoiceService constructor.
   *
   * @param \Civi\Financeextras\WorkflowMessage\CreditNoteInvoice $template
   */
  public function __construct(private CreditNoteInvoice $template) {
  }

  /**
   * Renders the credit note invoice message template.
   *
   * @param int $id
   *  Credit Note ID
   *
   * @return array
   *   Rendered message, consistent of 'subject', 'text', 'html'
   */
  public function render(int $id): array {
    $creditNote = $this->getCreditNoteData($id);

    $domain = CRM_Core_BAO_Domain::getDomain();
    $organisation = Contact::get()
      ->addSelect('image_URL')
      ->addWhere('id', '=', $domain->contact_id)
      ->execute()
      ->first();

    $this->template->setCreditNoteId($id);
    $this->template->setCreditNote($creditNote);
    $this->template->setTaxTerm($this->getTaxTerm());
    $this->template->setDomainName($domain->name ?? '');
    $this->template->setDomainLogo($organisation['image_URL']);
    $this->template->setBaseURL(CRM_Core_Config::singleton()->userFrameworkBaseURL);
    $this->template->setDomainLocation($this->getContactLocation($domain->contact_id));
    $this->template->setContactLocation($this->getContactLocation($creditNote['contact_id']));

    $rendered = $this->template->renderTemplate();
    $rendered['format'] = $rendered['format'] ?? $this->defaultInvoiceFormat();

    return $rendered;
  }

  /**
   * Returns credit note data along with allocations.
   *
   * @param int $id
   *  Credit Note ID
   *
   * @return array
   *   Credit note data
   */
  private function getCreditNoteData(int $id): array {
    $invoiceAllocationType = $this->getAllocationType('invoice');
    $creditNote = CreditNote::get()
      ->addWhere('id', '=', $id)
      ->addChain('items', CreditNoteLine::get()
        ->addWhere('credit_note_id', '=', '$id')
        ->addSelect('*', 'product_id.name', 'financial_type_id.name')
      )
      ->addChain('allocations', CreditNoteAllocation::get()
        ->addWhere('credit_note_id', '=', '$id')
        ->addSelect('*', 'type_id:label')
      )
      ->addChain('contact', Contact::get()
        ->addWhere('id', '=', '$contact_id'), 0
      )
      ->execute()
      ->first();

    $creditNote['date'] = date('M d, Y', strtotime($creditNote['date']));

    foreach ($creditNote['items'] as &$item) {
      $item['tax_rate'] = sprintf('%.2f', ($item['tax_amount'] * 100) / $item['line_total']);
    }

    $contributions = empty($creditNote['allocations']) ? [] : Contribution::get()
      ->addWhere('id', 'IN', array_column($creditNote['allocations'], 'contribution_id'))
      ->execute()
      ->getArrayCopy();
    $contributions = array_combine(array_column($contributions, 'id'), $contributions);

    foreach ($creditNote['allocations'] as $allocation) {
      $allocation['contribution'] = $contributions[$allocation['contribution_id']] ?? [];
      $allocation['date'] = date('j<\s\u\p>S</\s\u\p> F Y', strtotime($allocation['date']));
      $allocation['type_label'] = $allocation['type_id:label'];

      if ($allocation['type_id'] == $invoiceAllocationType) {
        $creditNote['invoice_allocations'][] = $allocation;

        continue;
      }
      $creditNote['refund_allocations'][] = $allocation;
    }

    $creditNote['taxRates'] = CreditNoteBAO::computeTotalAmount($creditNote['items'])['taxRates'] ?? [];

    return $creditNote;
  }

  /**
   * Gets contact location.
   *
   * @return array
   *   An array of address lines.
   */
  private function getContactLocation($contactId): array {
    $locParams = ['contact_id' => $contactId];
    $locationDefaults = \CRM_Core_BAO_Location::getValues($locParams);
    if (empty($locationDefaults['address'][1])) {
      return [];
    }
    $stateProvinceId = $locationDefaults['address'][1]['state_province_id'] ?? NULL;
    $stateProvinceAbbreviationDomain = !empty($stateProvinceId) ? \CRM_Core_PseudoConstant::stateProvinceAbbreviation($stateProvinceId) : '';
    $countryId = $locationDefaults['address'][1]['country_id'];
    $countryDomain = !empty($countryId) ? \CRM_Core_PseudoConstant::country($countryId) : '';

    return [
      'street_address' => CRM_Utils_Array::value('street_address', CRM_Utils_Array::value('1', $locationDefaults['address'])),
      'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', CRM_Utils_Array::value('1', $locationDefaults['address'])),
      'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', CRM_Utils_Array::value('1', $locationDefaults['address'])),
      'supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3', CRM_Utils_Array::value('1', $locationDefaults['address'])),
      'city' => CRM_Utils_Array::value('city', CRM_Utils_Array::value('1', $locationDefaults['address'])),
      'postal_code' => CRM_Utils_Array::value('postal_code', CRM_Utils_Array::value('1', $locationDefaults['address'])),
      'state' => $stateProvinceAbbreviationDomain,
      'country' => $countryDomain,
    ];
  }

  /**
   * Gets allocatino type value by name.
   *
   * @param string $name
   *  The allocation type name
   *
   * @return int
   *   The allocation type value
   */
  private function getAllocationType(string $name): int|null {
    $allocationTypes = \Civi\Api4\OptionValue::get()
      ->addSelect('value', 'name')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_allocation_type')
      ->addWhere('name', '=', $name)
      ->execute()
      ->first();

    return $allocationTypes['value'];
  }

  /**
   * Returns the default format to use for Invoice.
   *
   * @return array
   */
  private function defaultInvoiceFormat(): array {
    return [
      'margin_top' => 10,
      'margin_left' => 65,
      'metric' => 'px',
    ];
  }

  /**
   * Returns the tax term.
   */
  private function getTaxTerm() {
    $settings = Setting::get()
      ->addSelect('contribution_invoice_settings')
      ->execute()
      ->first()['value'];

    return $settings['tax_term'] ?? 'Tax';
  }

}
