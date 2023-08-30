<div class="fe-membership_type">
  <table>
    <tbody>
      <tr>
        <td class="label">{$form.fe_member_type.paid_member.label}</td>
        <td>{$form.fe_member_type.paid_member.html}</td>
      </tr>
      <tr>
        <td class="label">{$form.fe_member_type.free_member.label}</td>
        <td>{$form.fe_member_type.free_member.html}</td>
      </tr>
    </tbody>
  </table>
</div>
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
