{include file='tpl_inc/header.tpl'}
{if $action === 'bearbeiten'}
    {include file='tpl_inc/kupons_bearbeiten.tpl'}
{else}
    {include file='tpl_inc/kupons_uebersicht.tpl'}
{/if}
{include file='tpl_inc/footer.tpl'}
