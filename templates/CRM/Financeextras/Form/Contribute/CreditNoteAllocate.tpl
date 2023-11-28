<div id="bootstrap-theme">

  <div class="panel panel-default creditnote__allocate-form-panel">
    <div class="panel-body">
      <div class="row" style="margin: 2em 0em;">
        <div class="col-md-10">
          <table class="table">
            <tbody>
              <tr>
                <th><p>{ts}Remaining credit available to allocate{/ts}</p></th><td><p>{$creditNote.remaining_credit|crmMoney:$creditNote.currency}</p></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>


      <div class="row">
        <div class="col-md-10">
          <table class="table">
            <thead>
              <th>Contribution ID</th>
              <th>Invoice No</th>
              <th>Total Amount</th>
              <th>Amount Due</th>
              <th>Amount to Allocate</th>
              <th>Ref.</th>
            </thead>
            <tbody>
            {foreach from=$contributions item=contribution}
              <tr>
                <td>{$contribution.id}</td>
                <td>{$contribution.invoice_number}</td>
                <td>{$contribution.total_amount|crmMoney:$creditNote.currency}</td>
                <td>{$contribution.due_amount|crmMoney:$creditNote.currency}</td>
                <td>{$currencySymbol} {$form.item_amount[$contribution.id].html}</td>
                <td>{$form.item_ref[$contribution.id].html}</td>
              </tr>
            {/foreach}
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <div class="crm-submit-buttons panel-footer" style="display: flex; justify-content: space-between;">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  </div>
</div>
