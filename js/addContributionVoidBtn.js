'use strict';

(function () {
  /**
   * Appends the "Void Contribution" button to the contribution status row.
   *
   * Scoped to the contribution View/Add/Edit form container AND the status-row
   * class (see addContributionCreditNoteBtn.js) so it can never attach to the
   * Find Contributions search form, even when injected into the underlying
   * page by a contribution View pop-up.
   *
   * @param {Function} $ jQuery (CRM.$)
   * @param {string} btnUrl the contribution void URL
   */
  function addContributionVoidBtn ($, btnUrl) {
    const contributionVoidActionBtn = $('<div>')
      .css({ display: 'inline-flex', marginLeft: '10px' })
      .append(
        $('<a>')
          .addClass('button btn-creditnote-create btn btn-primary-outline small-popup')
          .css({ background: '#fff', border: '1px solid #2786c2', color: '#2786c2' })
          .attr('href', btnUrl)
          .append($('<span>').text('Void Contribution'))
      );

    $('form.CRM_Contribute_Form_ContributionView, form.CRM_Contribute_Form_Contribution')
      .find('tr.crm-contribution-form-block-contribution_status_id > td:nth-child(2)')
      .append(contributionVoidActionBtn);
  }

  if (typeof CRM !== 'undefined' && CRM.$) {
    CRM.$(function ($) {
      addContributionVoidBtn($, CRM.vars.financeextras.contribution_void_btn_url.replace(/&amp;/g, '&'));
    });
  }

  if (typeof module !== 'undefined' && module.exports) {
    module.exports = { addContributionVoidBtn };
  }
})();
