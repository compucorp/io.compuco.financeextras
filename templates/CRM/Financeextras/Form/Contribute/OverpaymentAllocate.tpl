<div class="crm-block crm-form-block">
  <div class="messages status no-popup">
    <i class="crm-i fa-info-circle" aria-hidden="true"></i>
    {$message}
  </div>

  <table class="form-layout-compressed">
    <tr>
      <td class="label">{ts}Contact{/ts}</td>
      <td class="content">{$contactName}</td>
    </tr>
    <tr>
      <td class="label">{ts}Amount to allocate{/ts}</td>
      <td class="content"><strong>{$overpaymentAmount}</strong></td>
    </tr>
  </table>

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
