{if !$refundAmountMethod }
<div class="messages status no-popup">
  <i aria-hidden="true" class="crm-i fa-info-circle"></i>
  {ts}Currently only "Stripe Extension" supports refunded amount API. So, other payment processor only shows original amount as available amount.{/ts}
</div>
{/if}
<div class="crm-block crm-form-block crm-payment-refund-form-block">
  <div class="crm-accordion-body">
    <div class="message help" >
      <i aria-hidden="true" class="crm-i fa-info-circle"></i>
      {ts}Refunds take 5-10 days to appear on a customer's statement{/ts}
    </div>
  </div>
  <table class="form-layout-compressed">
    <tr class="crm-payment-refund-form-block-contact">
      <td class="label">{$form.contact.label}</td>
      <td>{$form.contact.html}</td>
    </tr>
    <tr class="crm-payment-refund-form-block-paymentinfos">
      <td class="label"><label for="payment-row">{ts}Select Payment To Refund{/ts}</label></td>
      <td><table class="selector row-highlight payment-info">
        <tr>
          <th></th>
          <th>{ts}Date{/ts}</th>
          <th>{ts}Original Amount{/ts}</th>
          <th>{ts}Available Amount{/ts}</th>
          <th>{ts}Payment Processor{/ts}</th>
          <th>{ts}Processor Payment ID{/ts}</th>
        </tr>
        {foreach from=$paymentInfos item=paymentRow}
        <tr id="tr_{$paymentRow.financialTrxnId}">
          <td><input class="required crm-form-radio" {if !$paymentRow.available_amount} disabled=disabled {/if} value="{$paymentRow.financialTrxnId}" type="radio" id="payment-row" data-processorid="{$paymentRow.paymentProcessorId}" data-currency="{$paymentRow.currency}" name="payment_row" ></td>
          <td>{$paymentRow.date}</td>
          <td>{$paymentRow.amount|crmMoney:$paymentRow.currency}</td>
          <td class="available_amount_{$paymentRow.financialTrxnId}">{$paymentRow.available_amount|crmMoney:$paymentRow.currency}</td>
          <td>{$paymentRow.paymentProcessor}</td>
          <td>{$paymentRow.transactionId}</td>
        </tr>
        {/foreach}
      </table>
    </td>
  </tr>
  <tr  class="crm-payment-refund-form-block-refund_amount">
    <td class="label">{$form.amount.label}</td>
    <td>{$form.currency.html} {$form.amount.html}</td>
  </tr>
  <tr class="crm-payment-refund-form-block-reason">
    <td class="label">{$form.reason.label}</td>
    <td>{$form.reason.html}</td>
  </tr>
</table>
<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
</div>
