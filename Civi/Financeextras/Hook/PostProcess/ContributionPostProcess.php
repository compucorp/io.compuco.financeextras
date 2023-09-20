<?php

namespace Civi\Financeextras\Hook\PostProcess;

use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;

class ContributionPostProcess {

  /**
   * @param \CRM_Contribute_Form_Contribution $form
   */
  public function __construct(private \CRM_Contribute_Form_Contribution $form) {
  }

  public function handle() {
    $this->recordPayment();
  }

  public function recordPayment() {
    $values = $this->form->getSubmitValues();

    try {
      $transaction = \CRM_Core_transaction::create();

      if (!empty($values['fe_record_payment_check']) && !empty($values['fe_record_payment_amount'])) {
        $params = [
          'is_payment' => 1,
          'trxn_id' => $values['trxn_id'],
          'contribution_id' => $this->form->_id,
          'check_number' => $values['check_number'] ?? NULL,
          'card_type_id' => $values['card_type_id'] ?? NULL,
          'pan_truncation' => $values['pan_truncation'] ?? NULL,
          'total_amount' => $values['fe_record_payment_amount'],
          'trxn_date' => $values['receive_date'] ?? date('YmdHis'),
          'payment_instrument_id' => $values['payment_instrument_id'],
        ];

        \CRM_Financial_BAO_Payment::create($params);
      }
    }
    catch (\Throwable $th) {
      $transaction->rollback();
      \CRM_Core_Session::setStatus('Error creating contribution payment', ts('Contribution Payment Error'), 'error');
      \Civi::log()->error('Error creating contribution payment: ' . $th->getMessage());
    }

    \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($this->form->_id));
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
    return $formName === "CRM_Contribute_Form_Contribution";
  }

}
