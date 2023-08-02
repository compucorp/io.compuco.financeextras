CRM.$(function () {
  // pendingStatusId, accountsReceivablePaymentMethodId and handlePendingStatusSelection
  // variables are coming from the backend, and assigned in alterContent hook.
  var currentContributionStatus = CRM.$('#contribution_status_id').val();
  if (currentContributionStatus == pendingStatusId) {
    handlePendingStatusSelection();
  }

  CRM.$('#contribution_status_id').on('change', function() {
    if (this.value == pendingStatusId) {
      handlePendingStatusSelection();
    }
    else {
      CRM.$(paymentDetailsSectionSelector).show();
    }
  });

  // Show the payment method if "Payment Plan" option is selected on the membership form.
  CRM.$('#payment_plan_fields_tabs').on('click', function() {
    var contributionToggleValue = CRM.$('[name=contribution_type_toggle]').val();
    if (contributionToggleValue == 'payment_plan') {
      CRM.$(paymentDetailsSectionSelector).show();
    }
  });

  function handlePendingStatusSelection() {
    CRM.$("#payment_instrument_id").val(accountsReceivablePaymentMethodId).change();
    CRM.$(paymentDetailsSectionSelector).hide();
  }
});
