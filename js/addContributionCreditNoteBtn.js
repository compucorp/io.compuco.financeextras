CRM.$(function ($) {
  addCreditNoteBtn();

  /**
   * Add the Credit / Cancel action button beside the contribution status
   */
  function addCreditNoteBtn() {
    const btnUrl = CRM.vars.financeextras.creditnote_btn_url.replace(/&amp;/g, '&');
    const isView = CRM.vars.financeextras.is_contribution_view;
    let statusRow = $('tr.crm-contribution-form-block-contribution_status_id > td:nth-child(2)');
    const actionBtn = $('<div>').css({ display: 'inline-flex', marginLeft: '10px' }).append($('<a>').addClass('button no-popup btn-creditnote-create').attr('href', btnUrl).append($('<span>').text('Create New Credit Note')));
    
    if (isView) {
      statusRow = $("tr:contains('Contribution Status') td:nth-child(2)");
    }

    statusRow.append(actionBtn)
    $('#searchForm a.btn-creditnote-create').hide();
  }
});
