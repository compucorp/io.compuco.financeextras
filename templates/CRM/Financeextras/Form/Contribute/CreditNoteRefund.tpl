<div id="bootstrap-theme">

  <div class="panel panel-default creditnote__refund-form-panel">
    <div class="panel-body">
      <div class="form-hoizontal">
        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.contact_id.label}
          </div>
          <div class="col-sm-7 col-md-5">
            {$form.contact_id.html}
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.amount.label}
          </div>
          <div class="col-sm-7 col-md-5">
            <div class="row">
              <div class="col-sm-3">{$form.currency.html}</div> <div class="col-sm-9">{$form.amount.html}</div>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.date.label}
          </div>
          <div class="col-sm-7 col-md-5">
            {$form.date.html}
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.payment_instrument_id.label}
          </div>
          <div class="col-sm-7 col-md-5">
            {$form.payment_instrument_id.html}
          </div>
        </div>
        {include file='CRM/Core/BillingBlockWrapper.tpl'}

        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.trxn_id.label}
          </div>
          <div class="col-sm-7 col-md-5">
            {$form.trxn_id.html}
          </div>
        </div>


        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.fee_amount.label}
          </div>
          <div class="col-sm-7 col-md-5">
            <div class="row">
              <div class="col-sm-3">{$form.currency.html}</div> <div class="col-sm-9">{$form.fee_amount.html}</div>
            </div>
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2 control-label">
            {$form.reference.label}
          </div>
          <div class="col-sm-7 col-md-5">
            {$form.reference.html}
          </div>
        </div>
      </div>
    </div>

    <div class="crm-submit-buttons panel-footer">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
  </div>
</div>

{literal}
  <style>
  #payment_information div.label {
    text-align: left;
    padding-left: 0;
  }

  #payment_information > fieldset > div div.content {
    margin-left: 17%;
  }

  #payment_information > fieldset > legend {
    display: none;
  }
  </style>
{/literal}
