<?php

namespace Civi\Financeextras\Test\Helper;

use Civi\Api4\CreditNote;
use Civi\Api4\OptionValue;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;

/**
 * Credit note helper trait.
 */
trait CreditNoteTrait {

  /**
   * Returns list of available statuses.
   *
   * @return array
   *   Array of credit note statuses
   */
  public function getCreditNoteStatus() {
    $status = OptionValue::get()
      ->addSelect('id', 'value', 'name', 'label')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_status')
      ->execute();

    return $status;
  }

  /**
   * Returns fabricated crdit note data.
   *
   * @param array $default
   *   Default value.
   *
   * @return array
   *   Key-Value pair of a crdit note fields and values
   */
  public function getCreditNoteData(array $default = []) {
    $client = ContactFabricator::fabricate();

    return array_merge([
      'contact_id' => $client['id'],
      'cn_number' => NULL,
      'reference' => 'NILO',
      'currency' => 'GBP',
      'status_id' => $this->getCreditNoteStatus()[0]['value'],
      'description' => 'test',
      'comment' => 'test',
      'tax' => 0,
      'subtotal' => 0,
      'total_credit' => 0,
      'date' => '2022-08-09',
      'items' => [],
    ], $default);
  }

  /**
   * Returns fabricated credit note line data.
   *
   * @param array $default
   *   Default value.
   *
   * @return array
   *   Key-Value pair of a credit note line item fields and values
   */
  public function getCreditNoteLineData(array $default = []) {
    $quantity = rand(2, 9);
    $unitPrice = rand(50, 1000);

    return array_merge([
      'financial_type_id' => 1,
      'description' => 'test',
      'quantity' => $quantity,
      'unit_price' => $unitPrice,
      'tax_rate' => 0,
      'line_total' => $quantity * $unitPrice,
    ], $default);
  }

  /**
   * Creates credit note.
   *
   * @param array $params
   *   Extra paramters.
   *
   * @return array
   *   Created credit note
   */
  public function createCreditNote(array $params = []): array {
    $creditNote = $this->getCreditNoteData();
    $creditNote['items'][] = $this->getCreditNoteLineData();
    $creditNote['items'][] = $this->getCreditNoteLineData();

    if (!empty($params['items']['tax_rate'])) {
      $creditNote['items'][0]['tax_rate'] = $params['items']['tax_rate'];
    }

    $creditNote['id'] = CreditNote::save()
      ->addRecord($creditNote)
      ->execute()
      ->jsonSerialize()[0]['id'];

    return $creditNote;
  }

}
