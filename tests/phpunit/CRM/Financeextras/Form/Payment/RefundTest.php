<?php

/**
 * Tests for the credit card refund form.
 *
 * @group headless
 */
class CRM_Financeextras_Form_Payment_RefundTest extends BaseHeadlessTest {

  /**
   * The "also automatically create a credit note" checkbox must default to
   * checked. If it does not, a refund leaves the contribution with an amount
   * owing and the contribution status drops to "Pending (Incomplete
   * Transaction)".
   *
   * The checked state must come from the form's default values: a raw `checked`
   * HTML attribute on the element is not honoured by CiviCRM's QuickForm
   * checkbox, which is why the box previously rendered unticked.
   */
  public function testCreateCreditNoteCheckboxDefaultsToChecked() {
    $form = new CRM_Financeextras_Form_Payment_Refund();

    $defaults = $form->setDefaultValues();

    $this->assertEquals(
      1,
      $defaults['create_credit_note'] ?? NULL,
      'The "automatically create a credit note" checkbox must default to checked.'
    );
  }

}
