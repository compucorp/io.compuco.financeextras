<div class="crm-block crm-form-block">
  <table class="form-layout-compressed">
    <tbody>
    <tr>
      <td class="label">{$form.contact_id.label}</td>
      <td>{$form.contact_id.html}</td>
    </tr>

    <tr>
      <td class="label">{$form.invoice_template_id.label}</td>
      <td>{$form.invoice_template_id.html}</td>
    </tr>

    <tr>
      <td class="label">{$form.invoice_prefix.label}</td>
      <td>{$form.invoice_prefix.html}</td>
    </tr>

    <tr>
      <td class="label">{$form.next_invoice_number.label}</td>
      <td>{$form.next_invoice_number.html}</td>
    </tr>

    <tr>
      <td class="label">{$form.creditnote_prefix.label}</td>
      <td>{$form.creditnote_prefix.html}</td>
    </tr>

    <tr>
      <td class="label">{$form.next_creditnote_number.label}</td>
      <td>{$form.next_creditnote_number.html}</td>
    </tr>
    </tbody>
  </table>
  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
