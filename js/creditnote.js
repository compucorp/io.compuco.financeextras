CRM.$(function () {
  moveCreditNoteTabNextToContributionTab();

  /**
   * Moves the credit note tab to the appropratie position
   */
  function moveCreditNoteTabNextToContributionTab() {
    CRM.$('#tab_contributions').after(CRM.$('#tab_credit_note'));
    CRM.$('#contributions-subtab').after(CRM.$('#credit_note_subtab'));
  }
});
