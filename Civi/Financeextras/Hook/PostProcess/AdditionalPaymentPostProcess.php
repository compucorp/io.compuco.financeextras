<?php

namespace Civi\Financeextras\Hook\PostProcess;

use Civi\Financeextras\Event\ContributionPaymentUpdatedEvent;

class AdditionalPaymentPostProcess {

  /**
   * @param \CRM_Contribute_Form_AdditionalPayment $form
   */
  public function __construct(private \CRM_Contribute_Form_AdditionalPayment $form) {
  }

  public function handle() {
    \Civi::dispatcher()->dispatch(ContributionPaymentUpdatedEvent::NAME, new ContributionPaymentUpdatedEvent($this->form->get('id')));
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
    return $formName === "CRM_Contribute_Form_AdditionalPayment" && !empty($form->get('id'));
  }

}
