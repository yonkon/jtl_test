{include file='tpl_inc/header.tpl'}
{if $step === 'edit-group'}
    {include file='tpl_inc/auswahlassistent_gruppe.tpl'}
{elseif $step === 'edit-question'}
    {include file='tpl_inc/auswahlassistent_frage.tpl'}
{else}
    {include file='tpl_inc/auswahlassistent_uebersicht.tpl'}
{/if}
{include file='tpl_inc/footer.tpl'}
