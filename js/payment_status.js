CRM.$(function () {
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

  function handlePendingStatusSelection() {
    CRM.$("#payment_instrument_id").val(accountsReceivablePaymentMethodId).change();
    CRM.$(paymentDetailsSectionSelector).hide();
  }
});
