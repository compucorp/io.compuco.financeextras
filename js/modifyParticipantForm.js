CRM.$(function ($) {

  (function() {
    $('.fe-record_contribution-block').hide();

    observeEventFeeIsDisplayed();
  })();

  /**
   * The event fees block for paid event is fetched via AJAX after the page has loaded.
   * 
   * Therefore, we need to ensure that the AJAX fetch is completed 
   * before consolidating the payment fields and setting up event listeners.
   */
  function observeEventFeeIsDisplayed() {
    const observer = new window.MutationObserver(function () {
      if ($('.crm-event-eventfees-form-block-record_contribution').length && !$('tr.fe_record_contribution-block_row').length) {
        observer.disconnect();

        placePaymentFieldsTogether();
        toggleContributionBlock();
        togglePaymentBlock();
        setTotalAmount();

        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  function placePaymentFieldsTogether() {
    $('.fe-record_contribution-block').show();
    $('tr.crm-event-eventfees-form-block-price_set_amount').after(
      $('<tr>').addClass('fe_record_contribution-block_row').append($('<td>').attr('colspan', 2).append(
        $('.fe-record_contribution-block')
      ))
    )
    $('tr.crm-event-eventfees-form-block-financeextras_contribution-amount').after(
      $('tr.crm-event-eventfees-form-block-financial_type_id')
    )
    $('tr.crm-event-eventfees-form-block-trxn_id').before(
      $('tr.crm-event-eventfees-form-block-payment_instrument_id')
    )
    $('.crm-event-eventfees-form-block-financial_type_id label').prepend('Contribution ')
    $('#receive_date').parent().parent().parent().hide()
    $('.fe-contribution-date').html($('#receive_date').parent())
    $('.crm-event-eventfees-form-block-financial_type_id .description').hide()
    $('.fe-record_contribution-block #currency-symbol').text(window.symbol)
    $('#total_amount').before($('.fe-record_contribution-block #currency-symbol').clone().append(' '))
    $('.crm-event-eventfees-form-block-contribution_status_id').hide();
  }

  function togglePaymentBlock() {
    if ($('input#record_contribution').is(':checked')) {
      $('input:radio[name=fe_ticket_type][value=paid_ticket]').click();
    }else {
      $('#payment_information').hide();
    }
  
    $('input#record_contribution').on('input', () => {
      if ($('input#record_contribution').is(':checked')) {
        $('#billing-payment-block').show();
      }else {
        $('#billing-payment-block').hide();
      }
    })
  }

  function toggleContributionBlock() {
    const toggleBlock = () => {
      const isPaid = $('input:radio[name=fe_ticket_type][value=paid_ticket]').is(':checked')
      $('input#record_contribution').prop("checked", !isPaid).trigger('click')
      $('tr.crm-event-eventfees-form-block-record_contribution').toggle(isPaid)

      $('.fe-record_contribution-fields').toggle(isPaid)
    }

    //if no free ticket is selected, uncheck record payment and hide the record payment field
    toggleBlock();
    $('input:radio[name=fe_ticket_type][value=paid_ticket]:checked').on('change', 
      () => $('input#record_contribution').prop("checked", true).trigger('click')
    );

    $('input:radio[name=fe_ticket_type]').on('change', () => {
        toggleBlock()
      }
    );
  }

  function setTotalAmount() {
    $('input[name=fe_contribution_amount]').val($('#pricevalue').data('raw-total'));
    $('#pricevalue').on('change', function() {
      $('input[name=fe_contribution_amount]').val($('#pricevalue').data('raw-total'));
    });
  }
});
