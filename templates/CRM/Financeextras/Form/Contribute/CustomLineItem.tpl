<div>
  <table>
    <tbody>
      <tr class="crm-contribution-form-block-financeextras_custom_total_amount">
        <td class="label"></td>
        <td><span class="label"><strong>Total Amount:</strong>&nbsp;&nbsp;</span><span id="custom-total">0.00</span></td>
      </tr>
    </tbody>
  </table>
</div>

{literal}
  <script>
    CRM.$(function($) {
      const submittedRows = $.parseJSON('{/literal}{$lineItemSubmitted}{literal}');
      const action = '{/literal}{$action}{literal}';
      const nonEmtyFinancialTypes = $('table#info select[id^="item_financial_type_id_"]').filter(function() {
        return this.value;
      });
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
        // This ensures the lineitem table has been built before trying to manipulate its field.
        setTimeout(() => {
          $('#lineitem-add-block').after(
            $('<div>').addClass('price-set-alt')
            .append($('<div>').addClass('price-set-alt-or'))
            .append($('<div>').addClass('price-set-alt-select'))
            .append($('#lineitem-add-block > div:last-child'))
          )
          $('#totalAmount, #totalAmountORaddLineitem').hide()
          $('.price-set-alt-select').prepend( `<div id="lineItemSwitch" class="crm-hover-button">OR <a href="#">Switch back to using line items</a></div>`)
          $('#lineItemSwitch').css('display', 'block').on('click', () => $('#price_set_id').val('').change())

          const hasValues = nonEmtyFinancialTypes.each(function() {
              $(this).val(this.value).trigger('change');
          }).length > 0;
          if (!hasValues && isEmptyPriceSet && submittedRows.length <= 0) {
              $('#add-items').click();
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
        }, 500);

        const toggleLineItemOrPricesetFields = (show) => {
          if (!show) {
            $('#lineItemSwitch').show();
            $('#selectPriceSet').prepend($('#price_set_id'))
            $('#lineitem-add-block').hide();
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
        const recordPaymentAmount = $("input[name=fe_record_payment_amount]");
        recordPaymentAmount.on("totalChanged", function() {
          setCustomTotalAmount(recordPaymentAmount.val())
        })
        $('select[name=currency]').on('change', function() {
          setCustomTotalAmount(recordPaymentAmount.val())
        });
      }
      $('tr.crm-contribution-form-block-total_amount').after(
        $('tr.crm-contribution-form-block-financeextras_custom_total_amount')
      )
      $('#line-total').parent().hide()

      if (action == 2) {
        $("#add_item option[value='new']").remove();
        $('#Contribution > div.crm-block.crm-form-block.crm-contribution-form-block > table > tbody > tr:nth-child(3) > td.label').text('Line Items')
        
        $('#line-total').on('datachanged', function() {
          setCustomTotalAmount($('#line-total').data('raw-total'));
        });
      }
      if (isNotQuickConfig && action == 2) {
        $('#line-total').parent().show();
        $('#line-total').after($('#custom-total'));
        $("#add-another-item").css('display', 'none');
        $('tr.crm-contribution-form-block-financeextras_custom_total_amount').hide();
        $('.total_amount-section > div:first-child').text('Contribution total: '+CRM.vars.financeextras.contrib_total);
      }
      if (!isNotQuickConfig && action == 2) {
        $('#totalAmount, #totalAmountORaddLineitem, #totalAmountORPriceSet, #price_set_id').css('display', 'none')
      }
      if (action == 2 && !isEmptyPriceSet) {
        $('#line-total').parent().show();
        $('tr.crm-contribution-form-block-financeextras_custom_total_amount').hide();
      }

      function setCustomTotalAmount(amount) {
        const currencySelect = $('#currency').val() ?? CRM.vars.financeextras.contrib_currency ?? '';
        
        CRM.api4('Currency', 'format', {
          currency: currencySelect,
          value: amount
        }).then(function(results) {
          $('#custom-total').text(results[0]);
        });
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
  #pricesetTotal, #line-total {
    display: none;
  }
</style>
{/literal}
