<div id="bootstrap-theme" style="background-color: #fff;">
  <div class="alert alert-warning">
    <i aria-hidden="true" class="crm-i fa-info-circle"></i> {ts} {$popupMessage} {/ts}
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
