<div id="creditnote-tab">
  <div class="container">
  <view></view>
  </div>
</div>
<script type="text/javascript">
const contributionId = JSON.parse({ $contribution_id });
const contactId = JSON.parse({ $contact_id });
{literal}
    (function(angular, $, _) {
      const app = angular.module('creditnoteTab', ['fe-creditnote']);
      app.directive('view', function () {
      return {
        template: `<creditnote-create contribution-id=${contributionId} contact-id=${contactId}></creditnote-create>`,
      }
    });
    })(angular, CRM.$, CRM._);

    CRM.$(document).one('crmLoad', function() {
      angular.bootstrap(document.getElementById('creditnote-tab'), ['creditnoteTab']);
    });
{/literal}
</script>
