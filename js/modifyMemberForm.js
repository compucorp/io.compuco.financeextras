CRM.$(function ($) {
  const defaultPaymentMethod = CRM.$("#payment_instrument_id") && CRM.$("#payment_instrument_id").val() ? CRM.$("#payment_instrument_id").val() : null;

  (function() {
    setTotalAmount();
    togglePaymentBlock();
    toggleMembershipType();
    toggleContributionBlock();
    placePaymentFieldsTogether();
    hidePaymentFieldsOnPaymentToggler();
  })();

  function setTotalAmount() {
    const recordPaymentAmount = document.querySelector("input[name=fe_record_payment_amount]");
    observeElement('input[name=total_amount]', "value", function () {
      toggleMembershipType();
      recordPaymentAmount.value = $('#total_amount').val();
    });
    $('#total_amount').on('change', () => {
      recordPaymentAmount.value = $('#total_amount').val()
    })
  }

  function togglePaymentBlock() {
    const accountsReceivablePaymentMethod = CRM.vars.financeextras.accounts_receivable_payment_method;

    $('input[name=fe_record_payment_check]').prop("checked", true).trigger('change');

    $('input[name=fe_record_payment_check]').on('change', () => {
      const recordPayment = $('input[name=fe_record_payment_check]').is(':checked')
      if (!recordPayment) {
        CRM.$("#payment_instrument_id").val(accountsReceivablePaymentMethod).change();
      } else {
        CRM.$("#payment_instrument_id").val(defaultPaymentMethod).change();
      }

      $('tr.record_payment-block_row').toggle(recordPayment);
    });
  }

  function toggleMembershipType() {
    /* global membershipType */
    if (typeof membershipType !== 'undefined' && typeof membershipType['total_amount_numeric'] !== 'undefined') {
      if (membershipType['total_amount_numeric'] > 0) {
        $('.fe-membership_type').show()
      } else {
        $('.fe-membership_type').hide()
      }
    }

    if ((parseFloat($('#total_amount').val()) > 0 || $('input[name=record_contribution]').is(':checked')) && !$('input[name=fe_member_type][value=paid_member]').is(":checked")) {
      $('input[name=fe_member_type][value=paid_member]').prop("checked", true).trigger('change')
    }
    else if ((parseFloat($('#total_amount').val()) <= 0) && $('input[name=fe_member_type][value=paid_member]').is(":checked")) {
      $('input[name=fe_member_type][value=free_member]').prop("checked", true).trigger('change')
    }
  }

  function toggleContributionBlock() {
    toggleMembershipType();

    $('tr#contri').after(
      $('<tr>').addClass('fe-membership_type-row').append($('<td>').attr('colspan', 2).append(
        $('.fe-membership_type')
      ))
    );

    $('input:radio[name=fe_member_type]').on('change', () => {
      const isPaid = $('input:radio[name=fe_member_type][value=paid_member]').is(':checked');
      if ($('input#record_contribution').is(":checked") && !isPaid) {
        $('input#record_contribution').prop("checked", !isPaid).trigger('click')
      }
      if (!$('input#record_contribution').is(":checked") && isPaid) {
        $('input#record_contribution').prop("checked", !isPaid).trigger('click')
      }

    });

    $('tr#contri').hide();
  }

  function placePaymentFieldsTogether() {
    $('tr.crm-membership-form-block-receive_date').after(
      $('<tr>').addClass('record_payment-block_row').append($('<td>').attr('colspan', 2).append(
        $('.record_payment-block')
      ))
    )

    waitForElement($, 'input[name=contribution_type_toggle]',
      () => {
        $('tr.crm-membership-form-block-receive_date').before($('tr.crm-membership-form-block-financial_type_id'));
        $('tr.crm-contribution-form-block-financeextras_record_payment_amount').after(
          $('tr.crm-membership-form-block-payment_instrument_id')
        );
        $('tr.crm-membership-form-block-payment_instrument_id').after($('tr.crm-membership-form-block-trxn_id'))
        $('tr.crm-membership-form-block-trxn_id').after($('tr.crm-membership-form-block-billing'))

        $('tr.crm-membership-form-block-contribution_status_id').hide()
      }
    );

    $('tr.record_payment-block_row').before(
      $('<tr>').append($('<td>').attr('colspan', 2).append(
        $('.record_payment-block_check')
      ))
    )

    const symbol = CRM.vars.financeextras.currencySymbol;
    $('.crm-membership-form-block-total_amount label').text('Contribution Total Amount')
    $('.crm-membership-form-block-financial_type_id label').text('Contribution Financial Type')
    $('#total_amount').before($('<span>').text(`${symbol} `))
    $('.record_payment-block #currency-symbol').text(symbol)
  }

  function hidePaymentFieldsOnPaymentToggler() {
    $('li[data-selector="payment_plan"]').click( () => {
      $('tr.record_payment-block_row').show()
      $('div.record_payment-block_check').hide();
      $('tr.crm-contribution-form-block-financeextras_record_payment_amount').hide();
    });

    $('li[data-selector="contribution"]').click( () => {
      $('div.record_payment-block_check').show()
      togglePaymentBlock();
    });
  }

  /**
   * Triggers callback when element attribute changes.
   *
   * @param {object} $
   * @param {string} elementPath
   * @param {object} callBack
   */
  function waitForElement($, elementPath, callBack) {
    (new MutationObserver(function() {
      callBack($(elementPath));
    })).observe(document.querySelector(elementPath), {
      attributes: true,
    });
  }

  /**
   * Observes change in property value for an element.
   *
   * This method is used to listen for change in input fields
   * that doesn't emit a change event when their value changes.
   *
   * @param {string} elementPath
   * @param {string} property
   * @param {function} callback
   * @param {number} delay
   */
  function observeElement(elementPath, property, callback, delay = 0) {
    const element = document.querySelector(elementPath)
    const elementPrototype = Object.getPrototypeOf(element);
    if (Object.hasOwn(elementPrototype, property)) {
        const descriptor = Object.getOwnPropertyDescriptor(elementPrototype, property);
        Object.defineProperty(element, property, {
            get: function() {
                return descriptor.get.apply(this, arguments);
            },
            set: function () {
                const oldValue = this[property];
                descriptor.set.apply(this, arguments);
                const newValue = this[property];
                if (typeof callback == "function") {
                    setTimeout(callback.bind(this, oldValue, newValue), delay);
                }
            }
        });
    }
}

});
