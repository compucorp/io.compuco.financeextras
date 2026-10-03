<div id="bootstrap-theme">
{capture assign=newRateURL}{crmURL p="civicrm/exchange-rate/add" q="reset=1&action=add"}{/capture}
  <a
    accesskey="N" href="{$newRateURL|smarty:nodefaults}"
    class="btn btn-primary">
      <span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add Exchange Rate Value{/ts}</span>
  </a>
  <br /><br />

  <div class="panel panel-default">
    <div class="panel-body">
      <crm-angular-js modules="fe-exchange-rate" id="fe-exchange-rate-list">
        <afsearch-exchange-rate></afsearch-exchange-rate>
      </crm-angular-js>
    </div>
  </div>
</div>

<script type="text/javascript">
  {literal}
    CRM.$(function($) {
      $('#fe-exchange-rate-list').on('crmPopupFormSuccess', function() {
        $(this).find('crm-search-display-table').each(function() {
          var ctrl = angular.element(this).controller('crmSearchDisplayTable');
          if (ctrl && ctrl.getResultsPronto) {
            ctrl.rowCount = null;
            ctrl.getResultsPronto();
          }
        });
      });
    });
  {/literal}
</script>
