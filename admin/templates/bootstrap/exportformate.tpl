{include file='tpl_inc/header.tpl'}
{if $step === 'overview'}
    {include file='tpl_inc/exportformate_uebersicht.tpl'}
{elseif $step === 'edit'}
    {include file='tpl_inc/exportformate_neuer_export.tpl'}
{/if}
{include file='tpl_inc/footer.tpl'}
