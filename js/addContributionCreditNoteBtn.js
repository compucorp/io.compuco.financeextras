'use strict';

(function () {
  /**
   * Appends the "Create New Credit Note" button to the contribution status row.
   *
   * The insertion is scoped to the contribution View/Add/Edit form container
   * AND the status-row class, so the button can never attach to the Find
   * Contributions search form (`CRM_Contribute_Form_Search`) — even though this
   * script is injected into the underlying page when a contribution View is
   * opened as a pop-up (CRM.$ runs against the whole document).
   *
   * @param {Function} $ jQuery (CRM.$)
   * @param {string} btnUrl the credit note create URL
   */
  function addCreditNoteBtn ($, btnUrl) {
    const actionBtn = $('<div>')
      .css({ display: 'inline-flex', marginLeft: '10px' })
      .append(
        $('<a>').addClass('button no-popup btn-creditnote-create').attr('href', btnUrl)
          .append($('<span>').text(ts('Create New Credit Note')))
      );

    $('form.CRM_Contribute_Form_ContributionView, form.CRM_Contribute_Form_Contribution')
      .find('tr.crm-contribution-form-block-contribution_status_id > td:nth-child(2)')
      .append(actionBtn);
  }

  if (typeof CRM !== 'undefined' && CRM.$) {
    CRM.$(function ($) {
      addCreditNoteBtn($, CRM.vars.financeextras.creditnote_btn_url.replace(/&amp;/g, '&'));
    });
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = { addCreditNoteBtn };
  }
})();
