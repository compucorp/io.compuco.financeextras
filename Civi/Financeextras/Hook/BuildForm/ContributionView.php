<?php

namespace Civi\Financeextras\Hook\BuildForm;

use CRM_Financeextras_ExtensionUtil as E;

class ContributionView {

  private ?int $id;

  /**
   * @param \CRM_Contribute_Form_ContributionView $form
   */
  public function __construct(private \CRM_Contribute_Form_ContributionView $form) {
    $this->id = $this->form->get('id');
  }

  public function handle() {
    $this->addCreditNoteCancelAction();
  }

  private function addCreditNoteCancelAction() {
    if (!$this->id || $this->isContributionCancelled()) {
      return;
    }

    \Civi::resources()->add([
      'scriptFile' => [E::LONG_NAME, 'js/addContributionCreditNoteBtn.js'],
      'region' => 'page-header',
    ]);

    $url = \CRM_Utils_System::url('civicrm/contribution/creditnote', ['reset' => 1, 'action' => 'add', 'contribution_id' => $this->id]);
    \Civi::resources()->addVars('financeextras', ['is_contribution_view' => TRUE]);
    \Civi::resources()->addVars('financeextras', ['creditnote_btn_url' => $url]);
  }

  /**
   * Checks contribution has been cancelled.
   *
   * @return bool
   *   Array with recurring contribution's data.
   *
   * @throws \Civi\API\Exception
   */
  private function isContributionCancelled() {
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $this->id)
      ->addWhere('contribution_status_id:name', '=', 'Cancelled')
      ->setLimit(1)
      ->execute()
      ->first();

    return !empty($contribution);
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
    return $formName === "CRM_Contribute_Form_ContributionView" && ($form->getAction() & \CRM_Core_Action::VIEW);
  }

}
