{if $showTaxConversionTable}
  {assign var="widthPercentage" value="60%"}
  {assign var="padding" value="10px"}
{else}
  {assign var="widthPercentage" value="73%"}
  {assign var="padding" value="2px"}
{/if}

<table style="padding-top:{$padding};font-family: Arial, Verdana, sans-serif;" border="0" width="100%" cellpadding="8" cellspacing="0">
  {if $showTaxConversionTable}
    <tr>
      <td style="text-align:left;font-weight:bold;width:60%"></td>
      <td colspan="3" style="border: 1px solid; border-bottom: 0px; font-weight: bold;"><font size="1">*{$sales_tax_currency} Equivalent Conversion</font></td>
    </tr>
    <tr>
      <td style="text-align:left;font-weight:bold;width:60%"></td>
      <td colspan="3" style="border: 1px solid; border-top: 0px"><font size="1">1 {$sales_tax_currency} = {$rate_1_unit_tax_currency} {$currency}</font></td>
    </tr>
    <tr>
      <td style="text-align:left; font-weight:bold; width:60%"></td>
      <td style="text-align:center; border-left: 1px solid; border-bottom: 1px solid; font-weight: bold;"><font size="1">VAT Rate</font></td>
      <td style="text-align:center; border-bottom: 1px solid; font-weight: bold;"><font size="1">Net Amount</font></td>
      <td style="text-align:center; border-right: 1px solid; border-bottom: 1px solid; font-weight: bold;"><font size="1">VAT</font></td>
    </tr>
    {foreach from=$lineItem item=value key=priceset}
    <tr>
      <td style="text-align:left;font-weight:bold;width:60%"></td>
      <td style="text-align:center; border-left: 1px solid; border-bottom: 1px solid;"><font size="1">{if !empty($value.tax_rate)}{$value.tax_rate}{else}0{/if}%</font></td>
      {math assign="net_amount" equation='x/y' x=$value.subTotal y=$rate_1_unit_tax_currency} 
      <td style="text-align:center; border-bottom: 1px solid;"><font size="1">{$net_amount|string_format:"%.2f"}</font></td>
      {if !empty($value.tax_rate)}
      {math assign="vat" equation='a*b/100' a=$net_amount b=$value.tax_rate}
      <td style="text-align:center; border-right: 1px solid; border-bottom: 1px solid;"><font size="1">{$vat|string_format:"%.2f"}</font></td>
      {else}<td style="text-align:center; border-right: 1px solid; border-bottom: 1px solid;"><font size="1">0.00</font></td>{/if}
    </tr>
    {/foreach}
      <tr>
        <td style="text-align:left;font-weight:bold;width:60%"></td>
        <td colspan="3" style="border-top: 1px solid;"></td>
      </tr>
    {/if}
    {if $rate_vat_text}
      <tr>
        <td style="text-align:left; font-weight:bold; width:{$widthPercentage}"></td>
        <td colspan="3" style="border-bottom: 0px; font-weight: bold;"><font size="1"> {$rate_vat_text}</font></td>
      </tr>
    {/if}
  </table>
