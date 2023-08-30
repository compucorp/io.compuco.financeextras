<div class="fe-record_contribution-block">
  <table>
    <tbody>
      <tr>
        <td class="label">{$form.fe_ticket_type.paid_ticket.label}</td>
        <td>{$form.fe_ticket_type.paid_ticket.html}</td>
      </tr>
      <tr>
        <td class="label">{$form.fe_ticket_type.free_ticket.label}</td>
        <td>{$form.fe_ticket_type.free_ticket.html}</td>
      </tr>
    </tbody>
  </table>
  <fieldset class="fe-record_contribution-fields">
    <legend>Contribution Information</legend>
    <table>
    <tbody>
      <tr class="crm-event-eventfees-form-block-financeextras_contribution-amount">
        <td class="label" id="amount-label"> {$form.fe_contribution_amount.label}</td>
        <td><span id="currency-symbol"></span> {$form.fe_contribution_amount.html}</td>
      </tr>
      <tr>
        <td class="label"><label>{ts}Contribution Date{/ts}</label></td>
        <td class="fe-contribution-date"></td>
      </tr>
    </tbody>
  </table>
  </fieldset>
</div>
{literal}
  <style>
    .fe-record_contribution-fields > tbody > tr > td#amount-label,
    .crm-event-eventfees-form-block-total_amount > td.label {
      vertical-align: baseline;
    }
    #payment_information fieldset.pay-later_info-group legend {
      display: none;
    }
</style>
{/literal}
