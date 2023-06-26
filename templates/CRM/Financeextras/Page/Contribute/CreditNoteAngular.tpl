<div id="creditnote-tab">
  <div class="container">
  <view></view>
  </div>
</div>
<script type="text/javascript">
  const id = JSON.parse({ $id });
  const context = '{ $context }';
  {literal}
    (function(angular, $, _) {
      const app = angular.module('creditnoteTab', ['fe-creditnote']);
      app.directive('view', function () {
      const template = context == 'view' ? 'view' : 'create'
      return {
        template: `<creditnote-${template} id=${id} context=${context}></creditnote-create>`,
      }
    });
    })(angular, CRM.$, CRM._);

    CRM.$(document).one('crmLoad', function() {
      angular.bootstrap(document.getElementById('creditnote-tab'), ['creditnoteTab']);
    });
  {/literal}
</script>
