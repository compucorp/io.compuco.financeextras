<?php

namespace Civi\Api4\Action\CreditNote;

use CRM_Core_Transaction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\AbstractSaveAction;
use Civi\Api4\Generic\Traits\DAOActionTrait;
use CRM_Financeextras_BAO_CreditNote as CreditNoteBAO;
use CRM_Financeextras_BAO_CreditNoteLine as CreditNoteLineBAO;

/**
 * {@inheritDoc}
 */
class CreditNoteSaveAction extends AbstractSaveAction {
  use DAOActionTrait;

  /**
   * {@inheritDoc}
   */
  public function _run(Result $result) { // phpcs:ignore
    foreach ($this->records as &$record) {
      $record += $this->defaults;
      $this->formatWriteValues($record);
      $this->matchExisting($record);
      if (empty($record['id'])) {
        $this->fillDefaults($record);
      }
    }
    $this->validateValues();

    $resultArray = $this->writeRecord($this->records);

    $result->exchangeArray($resultArray);
  }

  /**
   * {@inheritDoc}
   */
  protected function writeRecord($items) {
    $transaction = CRM_Core_Transaction::create();

    try {
      $output = [];
      foreach ($items as $creditNote) {
        if (empty($creditNote['id'])) {
          $output[] = $this->createCreditNoteWithAccountingEntries($creditNote);
          continue;
        }

        $output[] = $this->updateCreditNote($creditNote);
      }

      return $output;
    }
    catch (\Exception $e) {
      $transaction->rollback();

      throw $e;
    }
  }

  /**
   * Creates new Credit note entity with financial entities
   *
   * @param array $data
   *  The credit note param
   *
   * @return array
   *   created credit note entity
   */
  public function createCreditNoteWithAccountingEntries(array $data) {
    $result = $this->createCreditNote($data);
    $creditNote = $result['creditNote'];
    $financialTrxn = $result['financialTrxn'];

    if (!empty($creditNote) && !empty($data['items'])) {
      $creditNote['items'] = CreditNoteLineBAO::createWithAcountingEntries($data['items'], $creditNote, $financialTrxn);
    }

    return $creditNote;
  }

  /**
   * Creates credit note entity
   *
   * @param array $data
   *  The credit note params
   *
   * @return array
   *   created credit note entity
   */
  private function createCreditNote(array $data) {
    $optionValues = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id:name', '=', 'financeextras_credit_note_status')
      ->addWhere('name', '=', 'open')
      ->execute();
    $data['status_id'] = $optionValues->first()['value'];

    $total = CreditNoteBAO::computeTotalAmount($data['items']);
    $data['subtotal'] = $total['totalBeforeTax'];
    $data['total_credit'] = $total['totalAfterTax'];
    $data['sales_tax'] = array_sum(array_column($total['taxRates'], 'value'));

    $finacialTypeId = $data['items'][0]['financial_type_id'];

    return CreditNoteBAO::createWithAccountingEntries($data, $finacialTypeId);
  }

  /**
   * Updates credit note entity
   *
   * @param array $data
   *  The credit note params
   *
   * @return array
   *   updated credit note entity
   */
  public function updateCreditNote(array $data) {
    $params = [
      'id' => $data['id'],
      'date' => $data['date'],
      'comment' => $data['comment'],
      'cn_number' => $data['cn_number'],
      'reference' => $data['reference'],
      'description' => $data['description'],
    ];

    return CreditNoteBAO::create($params);
  }

}
