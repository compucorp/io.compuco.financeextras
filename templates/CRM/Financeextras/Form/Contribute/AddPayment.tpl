<div class="record_payment-block_check">
  <table>
    <tbody>
      <tr class="crm-contribution-form-block-financeextras_record_payment_check">
        <td class="label">{$form.fe_record_payment_check.html}</td>
        <td>{$form.fe_record_payment_check.label}</td>
      </tr>
    </tbody>
  </table>
</div>
<div class="record_payment-block">
  <table>
    <tbody>
      <tr class="crm-contribution-form-block-financeextras_record_payment_amount">
        <td class="label" id="amount-label">{$form.fe_record_payment_amount.label} <span class="crm-marker" title="This field is required."> *</span></td>
        <td><span id="currency-symbol"></span> {$form.fe_record_payment_amount.html}</td>
      </tr>
    </tbody>
  </table>
</div>
{literal}
  <style>
    #payment_information > fieldset > legend {
      display: none;
    }
    .payment-details_group {
      display: none;
    }
    .crm-contribution-form-block-financeextras_record_payment_amount > #amount-label {
      vertical-align: baseline;
    }
    #Contribution .crm-form-block>.form-layout-compressed tr.record_payment-block_row tr.crm-contribution-fe-billing_row > td:first-child {
      padding-left: 0px;
    }
    #payment_information > fieldset > div > div > div.label {
      width: 149px;
      margin-right: 9px;
    }
  </style>
{/literal}
