<div id="bootstrap-theme">
  <div class="alert alert-warning text-center">
    <i class="fa fa-info-circle"></i>
    {ts}Are you sure you want to delete the exchange rate value?{/ts}
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

<script type="text/javascript">
  {literal}
    CRM.$(function($) {
      $("a[target='crm-popup']").on('crmPopupFormSuccess', function (e) {
        const val = CRM.$('input#exchange_date-01').val();
        CRM.$('input#exchange_date-01').val((new Date()).toDateString()).change();
        CRM.$('input#exchange_date-01').val(val).change();
      });
    });
  {/literal}
</script>
