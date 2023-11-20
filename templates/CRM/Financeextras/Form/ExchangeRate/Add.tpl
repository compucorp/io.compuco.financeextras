<div id="bootstrap-theme">
  <div class="panel panel-default fe__create-form-panel">
    <div class="crm-submit-buttons panel-heading">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
    <div class=" panel-body">
    <div class="form-hoizontal">
      {foreach from=$elementNames item=elementName}
      <div class="form-group row {$elementName}">
        <div class="col-sm-2 control-label">
          {$form.$elementName.label}
          {if in_array($elementName, $help)} 
            {help id="$elementName" file="CRM/Financeextras/Form/ExchangeRate/Add.hlp"}
          {/if}
        </div>
        <div class="col-sm-7 col-md-5">
          {$form.$elementName.html}
        </div>
      </div>
      {/foreach}
    </div>
  </div>

  <div class="crm-submit-buttons panel-footer">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
</div>

{literal}
  <script type="text/javascript">
    CRM.$(function ($) {
      $(document).ready(function () {
        $('form').preventDoubleSubmission();
      });

      $.fn.preventDoubleSubmission = function () {
        CRM.$(this).on('submit', function (e) {
          if ( $(this)[0].checkValidity() ) {
            CRM.$.blockUI();
          }
        });

        return this;
      };
    });
  </script>
{/literal}
