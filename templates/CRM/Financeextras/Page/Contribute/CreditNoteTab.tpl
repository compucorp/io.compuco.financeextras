
<li id="tab_credit_note" class="crm-tab-button ui-corner-all ui-tabs-tab ui-corner-top ui-state-default ui-tab">
  <a href="#credit_note_subtab" title="{ts}Credit Notes{/ts}">
    {ts}Credit Notes{/ts}
  </a>
</li>

<div id="credit_note_subtab" class="ui-tabs-panel ui-widget-content ui-corner-bottom">

  {if $action eq 16 and $permission EQ 'edit'}
  {capture assign=newCreditnotesURL}{crmURL p="civicrm/contribution/creditnote" q="reset=1&action=add&cid=`$contactId`&context=contribution"}{/capture}
    <div class="action-link">
      <a accesskey="N" href="{$newCreditnotesURL|smarty:nodefaults}" class="button no-popup"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Create New Credit Note{/ts}</span></a>
      <br /><br />
    </div>
  {/if}

  {if $creditNoteCount > 0}
    <div class='clear'></div>
    <crm-angular-js modules="fe-creditnote">
      <creditnote-list contact-id="{$contactId}"></creditnote-list>
    </crm-angular-js>

  {else}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}No credit notes have been recorded for this contact.{/ts}
    </div>
  {/if}
</div>

