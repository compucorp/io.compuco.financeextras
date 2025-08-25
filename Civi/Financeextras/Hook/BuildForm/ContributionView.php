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
    $this->handleButtons();
    $this->addContributionVoidAction();
  }

  private function addCreditNoteCancelAction() {
    if (!$this->id || $this->contributionHasStatus(['Cancelled', 'Refunded', 'Failed', 'Chargeback'])) {
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

  private function addContributionVoidAction() {
    $havePayments = !empty($this->getContributionPayments($this->id));
    $isAllowed = \CRM_Core_Permission::check('edit contributions');
    $isPendingWithoutPayments = !$this->contributionHasStatus(['Failed', 'Cancelled', 'Completed']) && !$havePayments;
    $isVoidable = $isPendingWithoutPayments || $this->contributionHasStatus(['Completed']);
    if (!$this->id || !$isVoidable || !$isAllowed) {
      return;
    }

    \Civi::resources()->add([
      'scriptFile' => [E::LONG_NAME, 'js/addContributionVoidBtn.js'],
      'region' => 'page-header',
    ]);

    $url = \CRM_Utils_System::url('civicrm/financeextras/contribution/void', ['reset' => 1, 'action' => 'void', 'id' => $this->id]);
    \Civi::resources()->addVars('financeextras', ['is_contribution_view' => TRUE]);
    \Civi::resources()->addVars('financeextras', ['contribution_void_btn_url' => $url]);
  }

  /**
   * Checks contribution has any of the given statuses.
   *
   * @return bool
   *   Whether the contribution has any of the given statuses.
   *
   * @throws \Civi\API\Exception
   */
  private function contributionHasStatus($statuses) {
    $contribution = \Civi\Api4\Contribution::get(FALSE)
      ->addWhere('id', '=', $this->id)
      ->addWhere('contribution_status_id:name', 'IN', $statuses)
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

  /**
   * Retrieves a contribution payments using APIv4
   *
   * @return array
   *   Array of Contribution Payments
   */
  public function getContributionPayments(): array {
    return \Civi\Api4\EntityFinancialTrxn::get(FALSE)
      ->addJoin('FinancialTrxn AS financial_trxn', 'INNER', ['financial_trxn_id', '=', 'financial_trxn.id'], ['financial_trxn.is_payment', '=', 1])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addWhere('entity_id', '=', $this->id)
      ->execute()
      ->getArrayCopy() ?? [];
  }

  private function handleButtons(): void {
    if (!$this->id || !$this->contributionHasStatus(['Cancelled', 'Failed'])) {
      return;
    }

    $buttonsToRemove = [ts('Email Invoice'), ts('Download Invoice'), ts('Download Invoice and Credit Note')];
    $buttons = $this->form->getTemplateVars('linkButtons');

    foreach ($buttons as $key => $button) {
      if (in_array($button['title'] ?? '', $buttonsToRemove)) {
        unset($buttons[$key]);
      }
    }

    $this->form->assign('linkButtons', $buttons);
  }

}
