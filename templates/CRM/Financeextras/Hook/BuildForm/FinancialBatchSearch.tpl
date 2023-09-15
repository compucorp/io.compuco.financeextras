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
    CRM.$('#financeextras_owner_org_row').insertAfter('tr.crm-financial-search-form-block-total');

    // Batch list page uses AJAX to filter the batch list,
    // but changes in the owner organisations field do
    // not trigger the filtration AJAX, because it is
    // added here in this template and not native to the
    // batch filtration form, to solve this, here we trigger
    // a "change" request on one of the native batch
    // filters (the batch title filter) in case any change
    // happens on the owner organisations field.
    CRM.$('#financeextras_owner_org_row :input')
      .change(function() {
        if (!CRM.$(this).hasClass('crm-inline-error')) {
          CRM.$('input #title').trigger('change');
        }
      })
  </script>
{/literal}
