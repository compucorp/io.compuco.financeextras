<div id="creditnote-tab">
  <div class="container">
  <view></view>
  </div>
</div>
{literal}
<script type="text/javascript">
    (function(angular, $, _) {
      const app = angular.module('creditnoteTab', ['fe-creditnote']);
      app.directive('view', function () {
      return {
        template: `<creditnote-create></creditnote-create>`,
      }
    });
    })(angular, CRM.$, CRM._);

    CRM.$(document).one('crmLoad', function() {
      angular.bootstrap(document.getElementById('creditnote-tab'), ['creditnoteTab']);
    });
</script>
{/literal}
