<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Core_Action;
use CRM_Financeextras_ExtensionUtil as E;
use Civi\Financeextras\Utils\OptionValueUtils;

class ParticipantCreate {

  /**
   * @param \CRM_Event_Form_Participant $form
   */
  public function __construct(private \CRM_Event_Form_Participant $form) {
  }

  public function handle() {
    $this->addContributionBlock();
  }

  /**
   * Adds support for recording contribution without payment
   *
   * By default, the Participant registration form enables users to optionally record
   * payment for a paid event. However, in cases where no payment is made for a paid event,
   * no contribution is currently being created.
   *
   * This methods adds new contribution amount field and rearranges the payment block, such that
   * when the user chooses free ticket, the contribution/payment fields will be hidden
   * On the other hand, if the user chooses a paid ticket, a contribution will be
   * created, and the user will have the option to record a payment for that contribution.
   */
  private function addContributionBlock() {
    $this->form->addElement('radio', 'fe_ticket_type', NULL, ts('Paid Ticket'), 'paid_ticket');
    $this->form->addElement('radio', 'fe_ticket_type', NULL, ts('Free Ticket'), 'free_ticket');
    $this->form->add('text', 'fe_contribution_amount', ts('Contribution Total Amount'), ['readonly' => TRUE]);

    $defaults = [
      'fe_ticket_type' => $this->getDefaultTicketType(),
      'fe_contribution_amount' => 0,
      'contribution_status_id' => OptionValueUtils::getValueForOptionValue('contribution_status', 'pending'),
    ];
    $this->form->setDefaults(array_merge($this->form->_defaultValues, $defaults));

    $accountsReceivablePaymentMethodId = array_search('accounts_receivable', \CRM_Contribute_BAO_Contribution::buildOptions('payment_instrument_id', 'validate'));
    \Civi::resources()->addVars('financeextras', ['accounts_receivable_payment_method' => $accountsReceivablePaymentMethodId]);

    \Civi::resources()->add([
      'scriptFile' => [E::LONG_NAME, 'js/modifyParticipantForm.js'],
      'region' => 'page-header',
    ]);
    \Civi::resources()->add([
      'template' => 'CRM/Financeextras/Form/Event/AddContribution.tpl',
      'region' => 'page-body',
    ]);
  }

  /**
   * Returns the default ticket type
   *
   * Paid Ticket, If:
   *  It's new and the event is a paid event
   *  It's edit and the event as a contribution,
   * Else, Free Ticket
   */
  private function getDefaultTicketType() {
    // Check if the form is being updated and has a participant ID
    if ($this->form->_id && $this->form->_action & CRM_Core_Action::UPDATE) {
      $participantPayment = new \CRM_Event_BAO_ParticipantPayment();
      $participantPayment->participant_id = $this->form->_id;
      $participantPayment->find(TRUE);

      // If a contribution ID is associated with the participant payment, set ticket type as 'paid_ticket'
      return !empty($participantPayment->contribution_id) ? 'paid_ticket' : 'free_ticket';
    }

    // Check if the event is a paid event based on its configuration
    $event = \Civi\Api4\Event::get(FALSE)
      ->addSelect('is_monetary')
      ->addWhere('id', '=', $this->form->_eID)
      ->execute()
      ->first();

    return ($event['is_monetary']) ? 'paid_ticket' : 'free_ticket';
  }

  /**
   * Checks if the hook should run.
   *
   * @param \CRM_Core_Form $form
   * @param string $formName
   *
   * @return bool
   */
  public static function shouldHandle($form, $formName) {
    $addOrUpdate = ($form->getAction() & \CRM_Core_Action::ADD) || ($form->getAction() & \CRM_Core_Action::UPDATE);
    return $formName === "CRM_Event_Form_Participant" && $addOrUpdate;
  }

}
