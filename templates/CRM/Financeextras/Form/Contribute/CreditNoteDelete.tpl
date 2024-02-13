<div id="bootstrap-theme">
  <div class="alert alert-warning text-center">
    <h1><i class="fa fa-question-circle"></i></h1>
    <h3>
      {ts}
        Deleting credit notes will permanently erase associated financial transactions and create gaps in numbering sequence needed for sales tax reporting. Exercise caution to avoid data loss and maintain record accuracy.
      {/ts}
    </h3>
    <h4>{ts}Are you sure you want to delete this Credit Note?{/ts}</h4>
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

<script type="text/javascript">
  {literal}
    CRM.$(function($) {
      $("a[target='crm-popup']").on('crmPopupFormSuccess', function (e) {
        CRM.refreshParent(e);
      });
    });
  {/literal}
</script>
