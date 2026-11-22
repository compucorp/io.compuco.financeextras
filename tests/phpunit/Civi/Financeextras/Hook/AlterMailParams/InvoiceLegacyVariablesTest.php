<?php

namespace Civi\Financeextras\Hook\AlterMailParams;

use BaseHeadlessTest;

/**
 * @group headless
 */
class InvoiceLegacyVariablesTest extends BaseHeadlessTest {

  public function testHandlesOnlyTheInvoiceWorkflow() {
    $this->assertTrue(InvoiceLegacyVariables::shouldHandle(['workflow' => 'contribution_invoice_receipt'], 'messageTemplate'));
    $this->assertTrue(InvoiceLegacyVariables::shouldHandle(['valueName' => 'contribution_invoice_receipt'], 'messageTemplate'));
    $this->assertFalse(InvoiceLegacyVariables::shouldHandle(['workflow' => 'contribution_offline_receipt'], 'messageTemplate'));
    $this->assertFalse(InvoiceLegacyVariables::shouldHandle([], 'messageTemplate'));
  }

  public function testAddsTheVariablesCoreNoLongerAssigns() {
    $contribution = $this->createContribution();

    // What core 6.20 passes: the contribution id and no legacy variables.
    $templateParams = [
      'workflow' => 'contribution_invoice_receipt',
      'tplParams' => ['id' => $contribution['id']],
    ];
    (new InvoiceLegacyVariables($templateParams, 'messageTemplate'))->handle();
    $tplParams = $templateParams['tplParams'];

    foreach (InvoiceLegacyVariables::VARIABLES as $name) {
      $this->assertArrayHasKey($name, $tplParams);
    }

    $statuses = \CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $this->assertEquals('legacy variables test', $tplParams['source']);
    $this->assertEquals($contribution['invoice_id'], $tplParams['invoice_id']);
    $this->assertNotEmpty($tplParams['invoice_number']);
    $this->assertEquals(100, $tplParams['amount']);
    $this->assertEquals(0, $tplParams['amountPaid']);
    $this->assertEquals(100, $tplParams['amountDue']);
    $this->assertEquals(100, $tplParams['subTotal']);
    $this->assertCount(1, $tplParams['lineItem']);
    $this->assertEquals(date('F j, Y'), $tplParams['invoice_date']);
    $this->assertEquals(1, $tplParams['is_pay_later']);
    $this->assertEquals('Pending', $tplParams['contributionStatusName']);
    $this->assertEquals(array_search('Pending', $statuses), $tplParams['contribution_status_id']);
    $this->assertEquals(array_search('Pending', $statuses), $tplParams['pendingStatusId']);
    $this->assertEquals(array_search('Refunded', $statuses), $tplParams['refundedStatusId']);
    $this->assertEquals(array_search('Cancelled', $statuses), $tplParams['cancelledStatusId']);
    $this->assertNotEquals($tplParams['contribution_status_id'], $tplParams['refundedStatusId']);
    $this->assertEquals(\CRM_Core_Config::singleton()->defaultCurrency, $tplParams['defaultCurrency']);
  }

  public function testLeavesVariablesAlreadyAssignedUntouched() {
    $contribution = $this->createContribution();

    // What core 6.4 passes: every legacy variable, some of them NULL.
    $assigned = array_fill_keys(InvoiceLegacyVariables::VARIABLES, 'assigned by core');
    $assigned['domain_phone'] = NULL;
    $assigned['id'] = $contribution['id'];
    $templateParams = [
      'workflow' => 'contribution_invoice_receipt',
      'tplParams' => $assigned,
    ];
    (new InvoiceLegacyVariables($templateParams, 'messageTemplate'))->handle();

    $this->assertSame($assigned, $templateParams['tplParams']);
  }

  public function testDoesNothingWithoutAContribution() {
    $templateParams = [
      'workflow' => 'contribution_invoice_receipt',
      'tplParams' => ['title' => 'x'],
    ];
    (new InvoiceLegacyVariables($templateParams, 'messageTemplate'))->handle();

    $this->assertSame(['title' => 'x'], $templateParams['tplParams']);
  }

  private function createContribution() {
    $contribution = civicrm_api3('Contribution', 'create', [
      'financial_type_id' => 'Donation',
      'receive_date' => '2022-11-11',
      'total_amount' => 100,
      'contact_id' => 1,
      'source' => 'legacy variables test',
      'invoice_id' => md5(uniqid('', TRUE)),
      'is_pay_later' => 1,
      'contribution_status_id' => 'Pending',
    ]);

    return civicrm_api3('Contribution', 'getsingle', ['id' => $contribution['id']]);
  }

}
