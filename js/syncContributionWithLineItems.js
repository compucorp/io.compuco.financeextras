/**
 * Keeps the contribution edit form in sync with the database after a line
 * item is edited or cancelled in a popup.
 */
CRM.$(function ($) {
  const LINE_ITEM_URL_PATTERN = /civicrm(?:\/|%2F)lineitem(?:\/|%2F)(edit|cancel)\b/i;
  const FINANCIAL_TYPE_SELECTOR = 'tr.crm-contribution-form-block-financial_type_id [name="financial_type_id"]';
  const STATUS_SELECTOR = 'tr.crm-contribution-form-block-contribution_status_id [name="contribution_status_id"]';
  const TOTAL_AMOUNT_SELECTOR = 'tr.crm-contribution-form-block-total_amount [name="total_amount"]';

  $(document).off('crmPopupFormSuccess.financeextrasSyncLineItems')
    .on('crmPopupFormSuccess.financeextrasSyncLineItems', 'a', function () {
      const href = $(this).attr('href') || '';
      const urlMatch = href.match(LINE_ITEM_URL_PATTERN);
      if (!urlMatch) {
        return;
      }

      const lineItemIdMatch = href.match(/[?&]id=(\d+)/);
      if (!lineItemIdMatch) {
        return;
      }

      let $form = $(this).closest('form');
      if (!$form.find(STATUS_SELECTOR + ', ' + FINANCIAL_TYPE_SELECTOR).length) {
        $form = $(document);
      }
      const isEdit = urlMatch[1].toLowerCase() === 'edit';

      CRM.api4('LineItem', 'get', {
        select: [
          'contribution_id',
          'contribution_id.financial_type_id',
          'contribution_id.financial_type_id:label',
          'contribution_id.contribution_status_id',
          'contribution_id.contribution_status_id:label',
          'contribution_id.total_amount',
          'contribution_id.tax_amount',
        ],
        where: [['id', '=', parseInt(lineItemIdMatch[1], 10)]],
      }, 0).then(function (lineItem) {
        if (!lineItem || !lineItem['contribution_id']) {
          return;
        }

        const formContributionId = (CRM.vars.financeextras || {}).edit_contribution_id;
        if (formContributionId && String(formContributionId) !== String(lineItem['contribution_id'])) {
          return;
        }

        // Cancelling a line item never changes the contribution financial type.
        if (isEdit) {
          syncOptionField(
            singleField($form, FINANCIAL_TYPE_SELECTOR),
            lineItem['contribution_id.financial_type_id'],
            lineItem['contribution_id.financial_type_id:label']
          );
        }
        syncOptionField(
          singleField($form, STATUS_SELECTOR),
          lineItem['contribution_id.contribution_status_id'],
          lineItem['contribution_id.contribution_status_id:label']
        );
        syncTotalAmount(
          $form,
          Number(lineItem['contribution_id.total_amount'] || 0),
          Number(lineItem['contribution_id.tax_amount'] || 0)
        );
      }, function (error) {
        console.error('financeextras: unable to refresh the contribution form after a line item change', error);
      });
    });

  /**
   * Returns the field matching the selector, or an empty set if it is
   * missing or ambiguous.
   */
  function singleField($form, selector) {
    const $field = $form.find(selector);
    return $field.length === 1 ? $field : $();
  }

  /**
   * Updates a select field (editable or frozen) without triggering "change",
   * so other handlers bound to the field are not run.
   */
  function syncOptionField($field, value, label) {
    if (!$field.length || value === null || value === undefined) {
      return;
    }

    value = String(value);
    if (String($field.val()) === value) {
      return;
    }

    if ($field.is('select')) {
      if (!$field.find('option').filter(function () { return this.value === value; }).length) {
        // Not an option the user can see on this form, leave it as it is.
        return;
      }
      if ($field.data('select2')) {
        // select2 3.x: updates the value and the widget without firing "change".
        $field.select2('val', value);
      }
      else {
        $field.val(value);
      }
      return;
    }

    setFrozenValue($field, value, label);
  }

  /**
   * Updates the total amount field and the line item total shown on the form.
   */
  function syncTotalAmount($form, totalAmount, taxAmount) {
    const $totalAmount = singleField($form, TOTAL_AMOUNT_SELECTOR);
    if ($totalAmount.length) {
      if ($totalAmount.is('[type="hidden"]')) {
        setFrozenValue($totalAmount, CRM.formatMoney(totalAmount, true), CRM.formatMoney(totalAmount, true));
      }
      else {
        $totalAmount.val(CRM.formatMoney(totalAmount - taxAmount, true));
      }
    }

    const $lineTotal = $form.find('#line-total');
    if ($lineTotal.length === 1) {
      $lineTotal.text(CRM.formatMoney(totalAmount))
        .data('raw-total', totalAmount)
        .trigger('datachanged');
    }
  }

  /**
   * Updates a frozen field, which QuickForm renders as a text label
   * immediately followed by a hidden input.
   */
  function setFrozenValue($input, value, label) {
    $input.val(value);

    const labelNode = $input[0].previousSibling;
    if (label && labelNode && labelNode.nodeType === Node.TEXT_NODE) {
      // Keep any leading whitespace (e.g. &nbsp;) that belongs to the template.
      labelNode.nodeValue = labelNode.nodeValue.match(/^\s*/)[0] + label;
    }
  }
});
