<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title></title>
</head>

<body>
  <div style="padding-top:25px;margin-right:50px;border-style: none; font-family: Arial, Verdana, sans-serif;">
    <table style="margin-top:5px;padding-bottom:5px; width:100%;" cellpadding="5" cellspacing="0">
      <tr>
        <td style="text-align:right;"><img src="{$domain_logo}" style="width: width: auto; max-height: 120px;"></td>
      </tr>
    </table>

    <div style="margin-top: 5px;">
      <table style="font-family: Arial, Verdana, sans-serif;" width="100%" height="100" border="0" cellpadding="5" cellspacing="0">
        <tr> <td><font size="1"><strong>Credit Note</strong></font></td> </tr>
      </table>
    </div>

    <div>
      <table style="font-family: Arial, Verdana, sans-serif;" width="100%" height="100" border="0" cellpadding="5" cellspacing="0">
        <tr>
          <td width="30%"><b><font size="1" align="center">{$credit_note.contact.display_name}</font></b></td>
          <td width="50%" valign="bottom"><b><font size="1" align="center">Credit Note Date</font></b></td>
          <td valign="bottom" style="white-space: nowrap"><b><font size="1" align="right">{$domain_name}</font></b></td>
        </tr>
        <tr>
          <td><font size="1" align="center">
            {if !empty($contact_location.street_address) }{$contact_location.street_address}{/if}
            {if !empty($contact_location.supplemental_address_1) }{$contact_location.supplemental_address_1}{/if}
          </font></td>
          <td><font size="1" align="right">{$credit_note.date}</font></td>
          <td style="white-space: nowrap"><font size="1" align="right">
            {if !empty($domain_location.street_address) }{$domain_location.street_address}{/if}
            {if !empty($domain_location.supplemental_address_1) }{$domain_location.supplemental_address_1}{/if}
          </font></td>
        </tr>
        <tr>
          <td><font size="1" align="center">
            {if !empty($contact_location.supplemental_address_2)  }{$contact_location.supplemental_address_2 }{/if}
            {if !empty($contact_location.state) }{$contact_location.state}{/if}
          </font></td>
          <td><b><font size="1" align="right">Credit Note Number</font></b></td>
          <td style="white-space: nowrap"><font size="1">
            {if !empty($domain_location.supplemental_address_2)  }{$domain_location.supplemental_address_2 }{/if}
            {if !empty($domain_location.state) }{$domain_location.state}{/if}
          </font></td>
        </tr>
        <tr>
          <td><font size="1" align="center">
            {if !empty($contact_location.city)  }{$contact_location.city }{/if}
            {if !empty($contact_location.postal_code) }{$contact_location.postal_code}{/if}
          </font></td>
          <td><font size="1" align="right">{$credit_note.cn_number}</font></td>
          <td style="white-space: nowrap"><font size="1">
            {if !empty($domain_location.city) }{$domain_location.city }{/if}
            {if !empty($domain_location.postal_code) }{$domain_location.postal_code}{/if}
          </font></td>
        </tr>
        <tr>
          <td><font size="1" align="center">
            {if !empty($contact_address.country)  }{$sales_order.clientAddress.country}{/if}
          </font></td>
          <td><b><font size="1" align="right">Reference:</font></b></td>
          <td style="white-space: nowrap"><font size="1">
            {if !empty($domain_location.country) }{$domain_location.country }{/if}
          </font></td>
        </tr>
        <tr>
          <td></td>
          <td><font size="1" align="right">{$credit_note.reference}</font></td>
          <td></td>
        </tr>
      </table>

      <div class="table" style="margin-bottom: 14px; margin-top: 50px;">
        <table rules="cols" style="padding-top:25px; border: none" width="100%" border="0" cellpadding="5" cellspacing="0">
          <thead>
            <tr class="head" style="background-color: #E0E0E0; border: 1px solid #000;">
              <th style="padding: 8px 10px; border: 0px solid #000; text-align: left; font-weight:bold;width:100%"><font size="1">{ts}Description{/ts}</font></th>
              <th style="padding: 8px 10px; border: 0px solid #000; text-align:right;font-weight:bold;white-space: nowrap;"><font size="1">{ts}Quantity{/ts}</font></th>
              <th style="padding: 8px 10px; border: 0px solid #000; text-align:right;font-weight:bold;white-space: nowrap;"><font size="1">{ts}Unit Price{/ts}</font></th>
              <th style="padding: 8px 10px; border: 0px solid #000; text-align:right;font-weight:bold;white-space: nowrap;"><font size="1">{ts}Sales Tax{/ts}</font></th>
              <th style="padding: 8px 10px; border: 0px solid #000; text-align:right;font-weight:bold;white-space: nowrap;"><font size="1">{ts}Amount GBP{/ts}</font></th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$credit_note.items key=k item=item}
            <tr style="{if ($k%2) == 0} background-color: #F5F5F5; {/if} border: 1px solid #000;">
              <td style="padding: 8px 10px;text-align: left;border: 0px solid #000;" > <font size="1">{$item.description}</font></td>
              <td style="padding: 8px 10px;text-align: right;border: 0px solid #000;"><font size="1">{$item.quantity}</font></td>
              <td style="padding: 8px 10px;text-align: right;border: 0px solid #000;"><font size="1">{$item.unit_price|crmMoney:$credit_note.currency}</font></td>
              <td style="padding: 8px 10px;text-align: right;border: 0px solid #000;"><font size="1">{if empty($item.tax_rate) }0{else}{$item.tax_rate}{/if}%</font></td>
              <td style="padding: 8px 10px;text-align: right;border: 0px solid #000;"><font size="1">{$item.line_total|crmMoney:$credit_note.currency}</font></td>
            </tr>
            {/foreach}
            <tr>
              <td colspan="2" style="border: none;"></td>
              <td colspan="2" style="text-align:right;white-space: nowrap;  border: none;"><font size="1">{ts}SubTotal(GBP) {/ts}</font></td>
              <td style="text-align:right; border: none;"><font size="1">{$credit_note.subtotal|crmMoney:$credit_note.currency}</font></td>
            </tr>
            {foreach from=$credit_note.taxRates item=tax}
              <tr>
                <td colspan="2" style="border: none;"></td>
                <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><font size="1">{ts}{$tax_term} {$tax.rate}%{/ts}</font></td>
                <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$tax.value|crmMoney:$credit_note.currency}</font></td>
              </tr>
              {/foreach}
              <tr>
                <td colspan="2" style="border: none;"></td>
                <td colspan="3" style="border: none;"><hr></hr></td>
              </tr>
              <tr>
                <td colspan="2" style="border: none;"></td>
                <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><b><font size="1">{ts}Total Amount(GBP){/ts}</font></b></td>
                <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$credit_note.total_credit|crmMoney:$credit_note.currency}</font></td>
              </tr>
              {if !empty($credit_note.invoice_allocations)}
                {foreach from=$credit_note.invoice_allocations item=allocation}
                  <tr>
                    <td colspan="2" style="border: none;"></td>
                    <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><font size="1"><b>{ts}Less Credit to {/ts}</b> 
                    {capture assign=contribUrl}{crmURL p='civicrm/contact/view/contribution'
                    q="reset=1&id=`$allocation.contribution_id`&action=view&context=fulltext" h=0 a=1 fe=1}{/capture}
                      <a href="{$contribUrl}">
                        {ts}Invoice{/ts} {$allocation.contribution.invoice_number}
                      </a>
                      </font>
                    </td>
                    <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$allocation.amount|crmMoney:$credit_note.currency}</font></td>
                  </tr>
                  <tr>
                    <td colspan="2" style="border: none;"></td>
                    <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$allocation.date}</font></td>
                    <td style="text-align:right;white-space: nowrap; border: none;"></td>
                  </tr>
                {/foreach}
              {/if}

              {if !empty($credit_note.refund_allocations)}
                <tr>
                  <td colspan="2" style="border: none;"></td>
                  <td colspan="3" style="border: none;"><hr></hr></td>
                </tr>
                {foreach from=$credit_note.refund_allocations item=allocation}
                  <tr>
                    <td colspan="2" style="border: none;"></td>
                    <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><font size="1"><b>{ts}Less {/ts}</b> 
                      {$allocation.type_label}
                      </font>
                    </td>
                    <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$allocation.amount|crmMoney:$credit_note.currency}</font></td>
                  </tr>
                  <tr>
                    <td colspan="2" style="border: none;"></td>
                    <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$allocation.date}</font></td>
                    <td style="text-align:right;white-space: nowrap; border: none;"></td>
                  </tr>
                {/foreach}
              {/if}
              {if !empty($credit_note.invoice_allocations) || !empty($credit_note.refund_allocations) }
                <tr>
                  <td colspan="2" style="border: none;"></td>
                  <td colspan="3" style="border: none;"><hr></hr></td>
                </tr>
                <tr>
                  <td colspan="2" style="border: none;"></td>
                  <td colspan="2" style="text-align:right;white-space: nowrap; border: none;"><b><font size="1">Remaining Credit(GBP)</font></b></td>
                  <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$credit_note.remaining_credit|crmMoney:$credit_note.currency}</font></td>
                </tr>
              {/if}

          </tbody>
        </table>
      </div>

      <div style="position: relative; margin-top: 85px;">
        <hr style="
            position: absolute;
            top: 6px;
            width: 100%;
            border: 1px dotted #000;
            border-style: none none dotted;
        ">
        <img src="{crmResURL ext=io.compuco.financeextras file=images/cut.png}" style="height: 31px; width: auto;">
      </div>

      <div>
        <table style="padding-top:5px; font-family: Arial, Verdana, sans-serif;" width="100%" height="100" border="0" cellpadding="6" cellspacing="0">
          <tr>
            <td colspan="4" style="border: none;"><b><font size="3">CREDIT ADVICE</font></b></td>
            <td colspan="1" style="text-align:right;white-space: nowrap; border: none;"><font size="1">Customer</font></td>
            <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$credit_note.contact.display_name}</font></td>
          </tr>
          <tr>
            <td colspan="4" style="border: none;"><font size="1">Please do not pay on this advice. Deduct the amount of this Credit Note from your next payment to us.</font></td>
            <td colspan="1" style="text-align:right;white-space: nowrap; border: none;"><font size="1">Credit Note#</font></td>
            <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$credit_note.cn_number}</font></td>
          </tr>
          <tr>
            <td colspan="4" style="border: none;"></td>
            <td colspan="2" style="border: none;"><hr></hr></td>
          </tr>
          <tr>
            <td colspan="4" style="border: none;"></td>
            <td colspan="1" style="text-align:right;white-space: nowrap; border: none;"><b><font size="1">{ts}Credit Amount(GBP){/ts}</font></b></td>
            <td style="text-align:right;white-space: nowrap; border: none;"><font size="1">{$credit_note.remaining_credit|crmMoney:$credit_note.currency}</font></td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</body>

</html>
