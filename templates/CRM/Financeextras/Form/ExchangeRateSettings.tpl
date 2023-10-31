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
            {help id="$elementName" file="CRM/Financeextras/Form/ExchangeRateSettings.hlp"}
          {/if}
        </div>
        <div class="col-sm-7 col-md-5">
          {$form.$elementName.html}
          {if $elementName eq 'display_on_invoice'}
            <span class="invoice-check"> Enable</span>
          {/if}
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
  <style>
    #bootstrap-theme > .fe__create-form-panel.panel > div.panel-body > div div.col-sm-2.control-label {
      text-align: right;
    } 
    #bootstrap-theme > .fe__create-form-panel.panel > div.panel-body > div div.col-sm-2.control-label > label {
      display: inline;
      text-align: right;
    }
    .invoice-check {
      vertical-align: sub;
    }
  </style>
{/literal}