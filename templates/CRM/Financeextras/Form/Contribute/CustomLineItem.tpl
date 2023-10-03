
{literal}
  <script>
    CRM.$(function($) {
      const submittedRows = $.parseJSON('{/literal}{$lineItemSubmitted}{literal}');
      const action = '{/literal}{$action}{literal}';
      isNotQuickConfig = '{/literal}{$pricesetFieldsCount}{literal}'
      const isEmptyPriceSet = !$('#price_set_id').length || $('#price_set_id').val() === ''

      if (action == 1) {
        $('tr.crm-contribution-form-block-financial_type_id').after(
          $('<tr>').addClass('currency-select').append(
            $('<td>').addClass('label').append(
              $('<label>').text('Currency')
            )
          ).append(
            $('<td>').append($('#currency').show())
          )
        )
      }

      if (action == 1) {
        // This dealy ensures the lineitem table has been built before trying to manipulate its field.
        setTimeout(() => {
          $('#lineitem-add-block').after(
            $('<div>').addClass('price-set-alt')
            .append($('<div>').addClass('price-set-alt-or'))
            .append($('<div>').addClass('price-set-alt-select'))
            .append($('#lineitem-add-block > div:last-child'))
          )
          $('#totalAmount, #totalAmountORaddLineitem').hide()
          $('#selectPriceSet').prepend( `<div id="lineItemSwitch" class="crm-hover-button">OR <a href="#">Switch back to using line items</a></div>`)
          $('#lineItemSwitch').css('display', 'block').on('click', () => $('#price_set_id').val('').change())

          if ((isEmptyPriceSet) && submittedRows.length <= 0) {
            $('#add-items').click()
          }
          toggleLineItemOrPricesetFields(isEmptyPriceSet);

          $('#price_set_id').on('change', function() {
            setTimeout(() => {
              toggleLineItemOrPricesetFields($(this).val() === '');
            }, 100);
          });

          $('#add-another-item').on('click', function() {
            if ($('#price_set_id')) {
              $('#totalAmountORPriceSet, #price_set_id').show();
            }
          });
        }, 100);

        const toggleLineItemOrPricesetFields = (show) => {
          if (!show) {
            $('#lineItemSwitch').show();
            $('#selectPriceSet').prepend($('#price_set_id'))
            $('#lineitem-add-block').hide();
            $('.price-set-alt').hide();
          } else {
            $('#lineItemSwitch').hide();
            $('#lineitem-add-block').show()
            $('#totalAmount, #totalAmountORaddLineitem, #totalAmountORPriceSet, #price_set_id').css('display', 'none')
            $('.price-set-alt').show()
            if (action != 2) {
              // On edit user can't switch from priceset to lineitem and viceversa.
              $('.price-set-alt-or').append($('#totalAmountORPriceSet').show())
              $('.price-set-alt-select').append($('#price_set_id').show())
            }
            if (action == 1) {
              $( "#total_amount").val(0)
            }
            $('#Contribution tr.crm-contribution-form-block-total_amount > td.label > label').text('Line Items')
          }
        }
        $('#Contribution tr.crm-contribution-form-block-total_amount > td.label > label').text('Line Items')
      }

      if (action == 2) {
        $("#add_item option[value='new']").remove();
        $('#Contribution > div.crm-block.crm-form-block.crm-contribution-form-block > table > tbody > tr:nth-child(3) > td.label').text('Line Items')
      }
      if (!isNotQuickConfig && action == 2) {
        $('#totalAmount, #totalAmountORaddLineitem, #totalAmountORPriceSet, #price_set_id').css('display', 'none')
      }
    })
  </script>
{/literal}

{literal}
<style>
  div#lineitem-add-block.status {
    background-color: #fff;
    border-color: #fff;
    padding: 0px 0px;
    margin: 0 0px 10px !important;
  }
  div#lineitem-add-block.status table#info {
    border: 1px solid #ddd;
    padding-bottom: 8px;
    margin-bottom: 8px;
  }
  div#lineitem-add-block.status #add-another-item {
    margin-bottom: 2px !important;
  }
  #choose-manual {
    display: none;
  }
  .price_set-section label {
    width: 100%;
  }
  .price-set-alt-select {
    margin: 8px 0px;
  }
  #pricesetTotal > #pricelabel {
    width: 30%;
  }
  #pricesetTotal > #pricelabel > span {
    margin-right: 5px;
    font-weight: bold;
  }
  #selectPriceSet div#lineItemSwitch {
    margin-left: 15px !important;
    text-align: left;
  }
  tr.line-item-row > td {
    padding-left: 5px !important;
  }
  .float-right {
    float: right;
  }
</style>
{/literal}
