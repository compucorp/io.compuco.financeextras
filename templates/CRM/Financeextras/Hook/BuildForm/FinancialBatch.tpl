<table id="financeextras_owner_org_table" class="form-layout">
  <tbody>
  <tr id="financeextras_owner_org_row">
    <td class="label">
        {$form.financeextras_owner_org_id.label}
    </td>
    <td>
        {$form.financeextras_owner_org_id.html}
    </td>
  </tr>
  </tbody>
</table>

{literal}
  <script type="text/javascript">
    CRM.$(function ($) {
      $('#financeextras_owner_org_table').insertAfter('#FinancialBatch fieldset.crm-collapsible');
    });
  </script>
{/literal}
