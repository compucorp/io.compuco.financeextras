<?php

namespace Civi\Financeextras\Hook\BuildForm;

use BaseHeadlessTest;
use Civi\Api4\FinancialType;
use Civi\Financeextras\Test\Fabricator\ContactFabricator;
use Civi\Financeextras\Test\Fabricator\ContributionFabricator;
use CRM_Core_Action;

/**
 * @group headless
 */
class ContributionCreateTest extends BaseHeadlessTest {

  /**
   * Resource name of the sync script ("<extension key>:<file>").
   *
   * A literal is used because the extension's classes are not loaded yet
   * when PHPUnit builds the test suite.
   *
   * @var string
   */
  private const SYNC_SCRIPT = 'io.compuco.financeextras:js/syncContributionWithLineItems.js';

  private $contribution;

  private $partiallyPaidStatusId;

  private $completedStatusId;

  public function setUp(): void {
    parent::setUp();

    // Resources are kept in static regions, so reset them between tests.
    \CRM_Core_Region::instance('page-header')->clear();
    \CRM_Core_Region::instance('html-header')->clear();

    $this->partiallyPaidStatusId = \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid');
    $this->completedStatusId = \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $contact = ContactFabricator::fabricate();
    $this->contribution = ContributionFabricator::fabricate([
      'financial_type_id' => FinancialType::get(FALSE)
        ->addSelect('id')
        ->addWhere('name', '=', 'Donation')
        ->execute()
        ->first()['id'],
      'receive_date' => date('Y-m-d'),
      'total_amount' => 100,
      'contact_id' => $contact['id'],
      'currency' => 'GBP',
    ]);
  }

  public function testLineItemSyncIsAddedOnUpdateFormWhenLineItemEditorIsInstalled() {
    $form = $this->buildForm(CRM_Core_Action::UPDATE, $this->contribution['id']);

    $this->runHook($form, TRUE);

    $this->assertNotNull(\CRM_Core_Region::instance('page-header')->get(self::SYNC_SCRIPT));
    $this->assertEquals($this->contribution['id'], $this->getFinanceExtrasVars()['edit_contribution_id'] ?? NULL);
  }

  public function testLineItemSyncIsNotAddedOnAddForm() {
    $form = $this->buildForm(CRM_Core_Action::ADD);

    $this->runHook($form, TRUE);

    $this->assertNull(\CRM_Core_Region::instance('page-header')->get(self::SYNC_SCRIPT));
    $this->assertArrayNotHasKey('edit_contribution_id', $this->getFinanceExtrasVars());
  }

  public function testLineItemSyncIsNotAddedWhenLineItemEditorIsNotInstalled() {
    $form = $this->buildForm(CRM_Core_Action::UPDATE, $this->contribution['id']);

    $this->runHook($form, FALSE);

    $this->assertNull(\CRM_Core_Region::instance('page-header')->get(self::SYNC_SCRIPT));
    $this->assertArrayNotHasKey('edit_contribution_id', $this->getFinanceExtrasVars());
  }

  public function testSubmittedStatusIsReplacedWithCurrentStatusOnUpdateForm() {
    $form = $this->buildForm(CRM_Core_Action::UPDATE, $this->contribution['id']);
    $this->addStatusField($form);
    // The form was loaded as "Completed", then a line item edit changed the
    // contribution to "Partially paid". The status is set directly in the
    // database, as the line item editor does, to avoid payment processing.
    \CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $this->contribution['id'], 'contribution_status_id', $this->partiallyPaidStatusId);
    $form->_submitValues['contribution_status_id'] = $this->completedStatusId;

    $this->runHook($form, TRUE);

    $this->assertEquals($this->partiallyPaidStatusId, $form->_submitValues['contribution_status_id']);
  }

  public function testStatusIsNotAddedToSubmitValuesWhenFormIsNotSubmitted() {
    $form = $this->buildForm(CRM_Core_Action::UPDATE, $this->contribution['id']);
    $this->addStatusField($form);

    $this->runHook($form, TRUE);

    $this->assertArrayNotHasKey('contribution_status_id', $form->_submitValues);
  }

  public function testSubmittedStatusIsNotChangedOnAddForm() {
    $form = $this->buildForm(CRM_Core_Action::ADD);
    $this->addStatusField($form);
    $form->_submitValues['contribution_status_id'] = $this->completedStatusId;

    $this->runHook($form, TRUE);

    $this->assertEquals($this->completedStatusId, $form->_submitValues['contribution_status_id']);
  }

  private function buildForm(int $action, ?int $contributionId = NULL): \CRM_Contribute_Form_Contribution {
    $form = new \CRM_Contribute_Form_Contribution();
    $form->controller = new \CRM_Core_Controller();
    $form->setAction($action);
    $form->_id = $contributionId;

    return $form;
  }

  private function addStatusField(\CRM_Contribute_Form_Contribution $form): void {
    $form->add('select', 'contribution_status_id', ts('Contribution Status'),
      \CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'create')
    );
  }

  /**
   * Runs the hook with the line item editor install status stubbed.
   */
  private function runHook(\CRM_Contribute_Form_Contribution $form, bool $isLineItemEditorInstalled): void {
    $hook = new class($form, $isLineItemEditorInstalled) extends ContributionCreate {

      public function __construct(\CRM_Contribute_Form_Contribution $form, private bool $lineItemEditorInstalled) {
        parent::__construct($form);
      }

      protected function isLineItemEditorInstalled(): bool {
        return $this->lineItemEditorInstalled;
      }

    };

    $hook->handle();
  }

  private function getFinanceExtrasVars(): array {
    return \Civi::resources()->getSettings()['vars']['financeextras'] ?? [];
  }

}
