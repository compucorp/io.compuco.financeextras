{* {include file="CRM/Contact/Form/Task/Email.tpl"} *}

<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
  {ts}You can email a credit note from this screen. The credit note will be attached as a PDF to your email.{/ts}
</div>
<table class="form-layout-compressed">
  <tr id="selectEmailFrom" class="crm-contactEmail-form-block-fromEmailAddress crm-email-element">
    <td class="label">{$form.from_email_address.label}</td>
    <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp"}</td>
  </tr>
  <tr class="crm-contactEmail-form-block-recipient">
    <td class="label">{$form.to.label}</td>
    <td>
      {$form.to.html} {help id="id-to_email" file="CRM/Contact/Form/Task/Email.hlp"}
    </td>
  </tr>
  <tr class="crm-email-element crm-contactEmail-form-block-cc_id">
    <td class="label">{$form.cc_id.label}</td>
    <td>
        {$form.cc_id.html}
    </td>
  </tr>
  <tr class="crm-contactEmail-form-block-template">
    <td class="label">{$form.template.label}</td>
    <td>{$form.template.html}</td>
  </tr>
  <tr class="crm-contactEmail-form-block-subject">
       <td class="label">{$form.subject.label}</td>
       <td>
         {$form.subject.html|crmAddClass:huge}&nbsp;
         <input class="crm-token-selector big" data-field="subject" />
         {help id="id-token-subject" tplFile=$tplFile isAdmin=$isAdmin file="CRM/Contact/Form/Task/Email.hlp"}
       </td>
    </tr>
</table>

{include file="CRM/Contact/Form/Task/EmailCommon.tpl" noAttach=0}

<div class="spacer"></div>
<div class="crm-submit-buttons">
  {$form.buttons.html}
</div>

{literal}
<script>

  CRM.$(function($) {
    $('.crm-plaint_text_email-accordion').hide();
    $('#attachments').parent().parent().hide()
    $('#saveTemplate').parent().hide();
    const cancelBtn = cj( "button:contains('Previous')" )
    cancelBtn.html(cancelBtn.html().replace('Previous', 'Cancel'))
    var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1' h=0 }{literal}";

    var $form = $("form.{/literal}{$form.formClass}{literal}");
    function emailSelect(el, prepopulate) {
      $(el, $form).data('api-entity', 'contact').css({width: '40em', 'max-width': '90%'}).crmSelect2({
        minimumInputLength: 1,
        multiple: true,
        ajax: {
          url: sourceDataUrl,
          data: function(term) {
            return {
              name: term
            };
          },
          results: function(response) {
            return {
              results: response
            };
          }
        }
      }).select2('data', prepopulate);
    }

    {/literal}
      var toContact = {if $toContact}{$toContact}{else}''{/if};
    {literal}
    emailSelect('#to', toContact);
  })

  function selectValue(val) {
    console.log(val)
    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/template' h=0 }"{literal};

    cj.post( dataUrl, {tid: val}, function( data ) {
      CRM.wysiwyg.setVal('#html_message', data.msg_html || '');
      cj("#subject").val( data.subject || '' );
    });
  }
</script>
{/literal}
