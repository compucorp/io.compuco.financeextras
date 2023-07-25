CRM.$(function ($) {

  (function() {
    setTotalAmount();
    hideStatusField();
    setAmountCurencySymbol();
    toggleRecordPaymentBlock();
    placePaymentFieldsTogether();
  })();

  function setTotalAmount() {
    const recordPaymentAmount = document.querySelector("input[name=fe_record_payment_amount]");
    $('#total_amount').on("change", function() {
      recordPaymentAmount.value = $('#total_amount').val();
    });
  
    $('#price_set_id').on('change', function() {
      if (($(this).val() !== '')) {
        $('#pricevalue').on('change', function() {
          recordPaymentAmount.value = $('#pricevalue').data('raw-total');
        });
      }
    })
  }

  function hideStatusField() {
    CRM.$('.crm-contribution-form-block-contribution_status_id').hide();
  }

  function setAmountCurencySymbol() {
    const setSymbol = () => {
      const currencySelect = $('#currency').val();
      const currencySymbol = CRM.vars.financeextras.currencies[currencySelect];

      $('.record_payment-block #currency-symbol').text(currencySymbol)
    };
  
    setSymbol();
    $('select[name=currency]').on('change', function() {
      setSymbol();
    });
  }

  function toggleRecordPaymentBlock() {
    const recordPaymentCheck = document.querySelector("input[name=fe_record_payment_check]");
    const toggle = (checked)  => {
      if (checked) {
        $('.record_payment-block').show();
      } else {
        $('.record_payment-block').hide();
      }
    }

    toggle(recordPaymentCheck.checked)
    recordPaymentCheck.addEventListener('change', function() {
      toggle(this.checked)
    });
  }

  function placePaymentFieldsTogether() {
    $('tr.crm-contribution-form-block-receive_date').after(
      $('<tr>').addClass('record_payment-block_row').append($('<td>').attr('colspan', 2).append(
        $('.record_payment-block')
      ))
    )
    $('tr.record_payment-block_row').before(
      $('<tr>').append($('<td>').attr('colspan', 2).append(
        $('.record_payment-block_check')
      ))
    )
    $('tr.crm-contribution-form-block-financeextras_record_payment_amount').after(
      $('tr.crm-contribution-form-block-payment_instrument_id')
    )
    $('tr.crm-contribution-form-block-payment_instrument_id').after($('tr.crm-contribution-form-block-trxn_id'))
    $('tr.crm-contribution-form-block-trxn_id').after(
      $('<tr>').addClass('crm-contribution-fe-billing_row').append($('<td>').attr('colspan', 2).append(
        $('div#billing-payment-block')
      ))
    );
    $('tr.crm-contribution-fe-billing_row').after($('tr.crm-contribution-form-block-receipt_date'));
    $('#payment_information > fieldset > legend').hide();
  }
});
