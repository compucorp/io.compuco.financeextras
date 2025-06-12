CRM.$(function ($) {
  addContributionVoidBtn();

  /**
   * Add the Contribution void action button beside the contribution status
   */
  function addContributionVoidBtn() {
    const btnUrl = CRM.vars.financeextras.contribution_void_btn_url.replace(/&amp;/g, '&');
    const isView = CRM.vars.financeextras.is_contribution_view;
    let statusRow = $('tr.crm-contribution-form-block-contribution_status_id > td:nth-child(2)');
    const contributionVoidActionBtn = $('<div>').css({ display: 'inline-flex', marginLeft: '10px' }).append($('<a>').addClass('button btn-creditnote-create btn btn-primary-outline small-popup').css({background:"#fff", border: '1px solid #2786c2', color: '#2786c2'}).attr('href', btnUrl).append($('<span>').text('Void Contribution')));
    
    if (isView) {
      statusRow = $("tr:contains('Contribution Status') td:nth-child(2)");
    }

    statusRow.append(contributionVoidActionBtn)
    $('#searchForm a.btn-creditnote-create').hide();
  }
});
