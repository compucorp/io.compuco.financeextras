<?php

use Civi\Financeextras\Hook\BuildForm\ContributionView;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;

/**
 * Server-side coverage for the ContributionView build-form hook that enqueues
 * the "Create New Credit Note" and "Void Contribution" action buttons.
 *
 * The client-side placement of those buttons (and the regression where they
 * leaked onto the Find Contributions search form) is covered by the jsdom
 * unit tests in tests/js/. These tests assert the hook only fires for the
 * contribution View form and only enqueues each script for the right
 * contribution states.
 *
 * @group headless
 */
class ContributionViewTest extends BaseHeadlessTest {

  private const CREDIT_NOTE_SCRIPT = 'addContributionCreditNoteBtn.js';
  private const VOID_SCRIPT = 'addContributionVoidBtn.js';

  /**
   * Builds a ContributionView form whose `id` resolves to the given
   * contribution (the hook reads `$form->get('id')` in its constructor).
   */
  private function makeContributionViewForm(int $contributionId): CRM_Contribute_Form_ContributionView {
    $form = new CRM_Contribute_Form_ContributionView();
    $form->controller = new CRM_Core_Controller();
    $form->set('id', $contributionId);

    return $form;
  }

  private function fabricateContribution(string $status): array {
    $contact = ContactFabricator::fabricate();

    return ContributionFabricator::fabricate([
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d'),
      'total_amount' => 100,
      'contact_id' => $contact['id'],
      'contribution_status_id' => $status,
      'currency' => 'GBP',
    ]);
  }

  private function renderPageHeader(): string {
    return CRM_Core_Region::instance('page-header')->render('');
  }

  public function testShouldHandleIsFalseForTheSearchForm(): void {
    $form = $this->makeContributionViewForm(1);

    $this->assertFalse(
      ContributionView::shouldHandle($form, 'CRM_Contribute_Form_Search'),
      'The hook must not run for the Find Contributions search form.'
    );
  }

  public function testButtonScriptsEnqueuedForCompletedContribution(): void {
    $contribution = $this->fabricateContribution('Completed');

    (new ContributionView($this->makeContributionViewForm((int) $contribution['id'])))->handle();

    $html = $this->renderPageHeader();
    $this->assertContains(self::CREDIT_NOTE_SCRIPT, $html);
    $this->assertContains(self::VOID_SCRIPT, $html);
  }

  public function testCreditNoteScriptNotEnqueuedForRefundedContribution(): void {
    $contribution = $this->fabricateContribution('Refunded');

    (new ContributionView($this->makeContributionViewForm((int) $contribution['id'])))->handle();

    $html = $this->renderPageHeader();
    $this->assertNotContains(self::CREDIT_NOTE_SCRIPT, $html);
  }

}
