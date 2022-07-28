/*global CRM, ts */

CRM.$(function ($) {
  'use strict';
  CRM.$(function ($) {
    let paymentRowCount = $(".payment-info tbody tr").length;
    if( paymentRowCount == 2 ) {
        let id = $(".payment-info td input").attr('id');
        $(".payment-info td input").prop('checked',true);
        let refundAmount = $("#refund_amount").val();
        let amount = $(".payment-info td.available_amount_" + id).html();
        let paymentProcessorId = $("#" + id).attr('data-processorid');
        let currency = $("#" + id).attr('data-currency');
        let amountSplit = amount.split(" ");
        $("[name='trxn_id']").val($(".payment-info td input").val());
        $("[name='available_amount']").val(amountSplit[1]);
        $("[name='payment_processor_id']").val(paymentProcessorId);
        $("[name='currency']").val(currency);
        if(!refundAmount){
            $("[name='amount']").val(amountSplit[1]);
        }
    }
    else {
        $(".payment-info td input").delegate("body", "click", function() {
            let id = $(this).attr('id');
            let amount = $(".available_amount_" + id).html();
            let amountSplit = amount.split(" ");
            let paymentProcessorId = $("#" + id).attr('data-processorid');
            let currency = $("#" + id).attr('data-currency');
            $("[name='available_amount']").val(amountSplit[1]);
            $("[name='amount']").val(amountSplit[1]);
            $("[name='payment_processor_id']").val(paymentProcessorId);
            $("[name='currency']").val(currency);
            $("[name='trxn_id']").val($("#" + id ).val());
            $('.payment-info tr').removeClass('selected');
            $('.payment-info #tr_'+id ).addClass('selected');
        });
    }
  });

});
