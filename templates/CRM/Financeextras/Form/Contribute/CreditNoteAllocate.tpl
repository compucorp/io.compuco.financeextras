<div id="bootstrap-theme">

  <div class="panel panel-default creditnote__allocate-form-panel">
    <div class="panel-body">
      <div class="row" style="margin: 2em 0em;">
        <div class="col-md-10">
          <table class="table">
            <tbody>
              <tr>
                <th><p>{ts}Remaining Credit Balance to Allocate{/ts}</p></th><td><p>{$creditNote.remaining_credit|crmMoney:$creditNote.currency}</p></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="row" style="margin: 2em 0em;">
        <div class="col-md-10">
          <table class="table">
            <tbody>
              <tr>
                <th><p>{ts}Include Completed Contributions{/ts} <span style="margin-left: 1em;">{$form.incl_completed.html}</span></p></th>
              </tr>
            </tbody>
          </table>
        </div>
      </div>


      <div class="row" style="margin: 2em 0em;">
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

<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    const url = new URLSearchParams(window.location.search);
    if (parseInt(url.get('completed_contribution')) == 1) {
      $('#incl_completed_1').prop('checked', true)
    }
    let isChecked = $('#incl_completed_1').is(':checked');

    function reloadPage(completedValue) {
      const url = new URLSearchParams(window.location.search);
      url.set('completed_contribution', completedValue)
      window.location.href = window.location.origin + window.location.pathname + '?' + url.toString();
    }

    $('#incl_completed_1').change(function() {
      isChecked = $(this).is(':checked');

      // Reload the page with the appropriate completed_contribution value
      reloadPage(isChecked ? 1 : 0);
    });
  });
  {/literal}
</script>
