<?php

namespace Civi\Financeextras\Hook\PostProcess;

use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;
use Civi\Financeextras\Utils\OptionValueUtils;

class ParticipantPostProcess {

  /**
   * @param \CRM_Event_Form_Participant $form
   */
  public function __construct(private \CRM_Event_Form_Participant $form) {
  }

  public function handle() {
    $this->recordContribution();
  }

  /**
   * Creates a contribution/payment record for the participant registeration
   *
   * Case 1: user selects free ticket, nothing to do, as a contribution shouldn't be created
   * Case 2: user select paid ticket and doesn't record payment,
   *    Create a new contribution here,
   *    Link the contribution to the participant record using ParticipantPayment entity
   * Case 3: user selects paid ticket and also records payment,
   *    It means a pending contribution has been created, here we record payment for the
   *    contribution and also ensure the contribution has the expected status
   *    as per the payment value.
   */
  public function recordContribution() {
    $values = $this->form->getSubmitValues();
    $ticketType = $values['fe_ticket_type'] ?? NULL;

    if (empty($ticketType) || $ticketType == 'free_ticket') {
      return;
    }

    try {
      $transaction = \CRM_Core_transaction::create();
      if (!$values['record_contribution']) {
        $contributionId = $this->createNewContribution();
      }
      else {
        $contributionId = $this->getParticipantContribution();
        $this->recordPayment($contributionId);
      }
    }
    catch (\Throwable $th) {
      $transaction->rollback();
      \CRM_Core_Session::setStatus('Error creating contribution', ts('Contribution Error'), 'error');
      \Civi::log()->error('Error creating contribution: ' . $th->getMessage());
    }

    if (empty($contributionId)) {
      return;
    }

    \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($contributionId));
  }

  private function createNewContribution() {
    $values = $this->form->getSubmitValues();
    $params = [
      'is_pay_later' => 1,
      'skipLineItem' => TRUE,
      'total_amount' => $values['fe_contribution_amount'],
      'financial_type_id' => $values['financial_type_id'],
      'currency' => \CRM_Core_Config::singleton()->defaultCurrency,
      'contact_id' => $this->form->_contactID,
      'receive_date' => $values['receive_date'] ?? date('Y-m-d'),
      'contribution_mode' => 'participant',
      'participant_id' => $this->form->_id,
      'source' => $this->getSource(),
      'contribution_status_id' => OptionValueUtils::getValueForOptionValue('contribution_status', 'Pending'),
    ];

    if (!empty($values['tax_amount'])) {
      $params['tax_amount'] = $values['tax_amount'];
    }

    $contribution = \CRM_Contribute_BAO_Contribution::create($params);
    $this->syncLineItem($contribution->id, $this->form->_id);

    $participantPaymentParams = [
      'participant_id' => $this->form->_id,
      'contribution_id' => $contribution->id,
    ];
    \CRM_Event_BAO_ParticipantPayment::create($participantPaymentParams);

    return $contribution->id;
  }

  private function getParticipantContribution() {
    $participantPayment = new \CRM_Event_BAO_ParticipantPayment();
    $participantPayment->participant_id = $this->form->_id;
    $participantPayment->find(TRUE);

    return $participantPayment->contribution_id;
  }

  private function getSource() {
    if ($this->form->getSubmitValues()['source']) {
      return $this->form->getSubmitValues()['source'];
    }

    $event = \Civi\Api4\Event::get()
      ->addSelect('title')
      ->addWhere('id', '=', $this->form->_eventId)
      ->execute()->first();

    return ts('%1 : Offline registration (by %2)', [
      1 => $event['title'],
      2 => \CRM_Core_Session::singleton()->getLoggedInContactDisplayName(),
    ]);
  }

  private function syncLineItem($contributionId, $participantId) {
    $lineItems = \Civi\Api4\LineItem::get()
      ->addWhere('entity_table', '=', 'civicrm_participant')
      ->addWhere('entity_id', '=', $participantId)
      ->execute();

    foreach ($lineItems as $item) {
      $lineItem = new \CRM_Price_DAO_LineItem();
      $lineItem->id = $item['id'];
      $lineItem->entity_id = $participantId;
      $lineItem->contribution_id = $contributionId;
      $lineItem->save(FALSE);
    }
  }

  private function recordPayment($contributionId) {
    $values = $this->form->getSubmitValues();
    if (!empty($values['fe_total_amount'])) {
      $params = [
        'is_payment' => 1,
        'trxn_id' => $values['trxn_id'],
        'contribution_id' => $contributionId,
        'total_amount' => $values['fe_total_amount'],
        'is_send_contribution_notification' => FALSE,
        'check_number' => $values['check_number'] ?? NULL,
        'card_type_id' => $values['card_type_id'] ?? NULL,
        'pan_truncation' => $values['pan_truncation'] ?? NULL,
        'trxn_date' => $values['receive_date'] ?? date('YmdHis'),
        'payment_instrument_id' => $values['payment_instrument_id'],
      ];

      \CRM_Financial_BAO_Payment::create($params);
    }
  }

  /**
   * Checks if the hook should run.
   *
   * @param CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName) {
    return $formName === "CRM_Event_Form_Participant";
  }

}
