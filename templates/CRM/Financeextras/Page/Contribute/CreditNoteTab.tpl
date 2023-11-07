
<li id="tab_credit_note" class="crm-tab-button ui-corner-all ui-tabs-tab ui-corner-top ui-state-default ui-tab">
  <a href="#credit_note_subtab" title="{ts}Credit Notes{/ts}">
    {ts}Credit Notes{/ts} {if $creditNoteCount > 0}<em>{$creditNoteCount}</em> {/if}
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
    <div id="bootstrap-theme" class="creditnote__container">
    <div class="panel panel-default" id="creditnote__list">
      <div class="panel-body">
        <crm-angular-js modules="fe-creditnote">
          <afsearch-credit-notes options="{ldelim}contact_id: {$contactId}{rdelim}"></afsearch-credit-notes>
        </crm-angular-js>
      </div>
    </div>
  </div>

  {else}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}No credit notes have been recorded for this contact.{/ts}
    </div>
  {/if}
</div>
