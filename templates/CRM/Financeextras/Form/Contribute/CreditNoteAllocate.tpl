{crmStyle ext="io.compuco.financeextras" file="css/loader.css"}

<div id="bootstrap-theme">

  <div class="panel panel-default creditnote__allocate-form-panel">
    <div class="panel-body">
      <div class="row" style="margin: 2em 0em;">
        <div class="col-md-12">
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
        <div class="col-md-12">
          <table class="table">
            <tbody>
              <tr>
                <th><p>{ts}Show All Contributions{/ts} <span style="margin-left: 1em;">{$form.incl_all.html}</span></p></th>
              </tr>
            </tbody>
          </table>
        </div>
      </div>


      <div class="row" style="margin: 2em 0em;">
        <div class="col-md-12">
          <table class="table allocations-list">
            <thead>
              <th>Contribution ID</th>
              <th>Invoice No</th>
              <th>Total Amount</th>
              <th>Amount Due</th>
              <th>Amount to Allocate</th>
              <th>Ref.</th>
            </thead>
            <tbody>
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
  {literal} const currency= {/literal}"{$creditNote.currency}"
  {literal} const currencySymbol= {/literal}"{$currencySymbol}"
  {literal} const contactID = {/literal}"{$creditNote.contact_id}"
  {literal} const loaderGif = {/literal}"{crmResURL ext="io.compuco.financeextras" file="images/loading-overlay.gif"}"
  {literal}
  const loader = createLoadingIndicator('div.creditnote__allocate-form-panel', {"loadingImage": loaderGif});
  let dt = null;
  function createLoadingIndicator(target, options = {}) {
    const defaults = {
      loadingImage: false,
      showOnInit: true,
      loadingClass: "loader",
      wrapperClass: "loading-indicator-wrapper"
    };

    const config = { ...defaults, ...options };
    const $target = CRM.$(target);

    const $wrapper = CRM.$('<div>', { class: config.wrapperClass });
    const $helper = CRM.$('<span>', { class: 'loading-indicator-helper' });
    $indicator = config.loadingImage ? CRM.$('<img src="' + config.loadingImage + '" />') : CRM.$('<div class="' + config.loadingClass + '"></div>');

    $wrapper.append($helper).append($indicator);
    $target.append($wrapper);

    if (config.showOnInit) {
      $wrapper.removeClass('loader-hidden').addClass('loader-visible');
    } else {
      $wrapper.removeClass('loader-visible').addClass('loader-hidden');
    }

    return {
      show: () => {
        $wrapper.removeClass('loader-hidden').addClass('loader-visible');
      },
      hide: () => {
        $wrapper.removeClass('loader-visible').addClass('loader-hidden');
      },
      element: $wrapper
    };
  }
  function fetchContributions(includeAll = false) {
    loader.show();
    const statusFilter = includeAll
    ? ["contribution_status_id:name", "NOT IN", ["Cancelled", "Failed"]]
    : ["contribution_status_id:name", "IN", ["Pending", "Partially paid"]];

    CRM.api4('Contribution', 'get', {
      where: [
        ["contact_id", "=", contactID],
        ["currency", "=", currency],
        statusFilter
      ]
    }).then(function(contributions) {
      renderContributions(contributions)
      loader.hide();
    }, function(failure) {
      loader.hide();
    });
  }

  function renderContributions(contributions) {
    const $table = CRM.$('.allocations-list');
    const $tbody = $table.find('tbody');
    $tbody.empty();

    if (CRM.$.fn.DataTable.isDataTable($table)) {
      $table.DataTable().clear().destroy();
    }
    contributions.forEach(contribution => {
      const dueAmount = parseFloat(contribution.total_amount) - parseFloat(contribution.paid_amount || 0);

      const $row = CRM.$('<tr>');
      $row.append(`<td>${contribution.id}</td>`);
      $row.append(`<td>${contribution.invoice_number || ''}</td>`);
      $row.append(`<td>${currencySymbol} ${Number(contribution.total_amount).toFixed(2)}</td>`);
      $row.append(`<td>${currencySymbol} ${Number(dueAmount).toFixed(2)}</td>`);

      const $amountInput = CRM.$('<input>', {
        type: 'number',
        name: `item_amount[${contribution.id}]`,
        class: 'crm-form-text crm-form-number',
        value: '',
        step: '0.01',
        min: 0
      });
      const $refInput = CRM.$('<input>', {
        type: 'text',
        name: `item_ref[${contribution.id}]`,
        class: 'crm-form-text',
        value: ''
      });

      $row.append(CRM.$('<td>').append(currencySymbol + ' ').append($amountInput));
      $row.append(CRM.$('<td>').append($refInput));

      $tbody.append($row);
    });

    dt = CRM.$('.allocations-list').DataTable({
      dom: '<t><"main-wrapper"<"inner-wrapper"p><"inner-wrapper"l><"info-wrap"i>>',
      language: {
        lengthMenu: 'Page Size _MENU_'
      },
      paging: true,
      pageLength: 10, // Set a default page length
      destroy: true // Ensure reinitialization doesn't break things
    });
  }

  CRM.$(function($) {
    let isChecked = $('#incl_all_1').is(':checked');
    fetchContributions(isChecked)

    $('#incl_all_1').change(function() {
      isChecked = $(this).is(':checked');

      // Reload the page with the appropriate all_contribution value
      fetchContributions(isChecked)
    });

    $('.CRM_Financeextras_Form_Contribute_CreditNoteAllocate').on('submit', function(e) {
      var form = this;

      // Encode a set of form elements from all pages as an array of names and values
      var params = dt.$('input,select,textarea').serializeArray();

      // Iterate over all form elements
      $.each(params, function() {
          // If element doesn't exist in DOM
          if(!$.contains(document, form[this.name])){
            // Create a hidden element
            $(form).append(
                $('<input>')
                  .attr('type', 'hidden')
                  .attr('name', this.name)
                  .val(this.value)
            );
          }
      });
    });
  });
  {/literal}
</script>

<style>
  {literal}
    .crm-container .creditnote__allocate-form-panel .dataTables_wrapper {
      box-shadow: unset !important;
    }
    #DataTables_Table_0 > thead > tr > th,
    tbody > tr > td {
      text-align: center !important;
    }
    .main-wrapper {
      width: 100%;
      margin-top: 24px;
    }
    .crm-container .dataTables_wrapper .info-wrap > .dataTables_info {
      padding-left: 0px;
    }
  {/literal}
</style>
