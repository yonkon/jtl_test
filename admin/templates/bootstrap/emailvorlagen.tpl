{include file='tpl_inc/header.tpl'}
{if $step === 'uebersicht'}
    {include file='tpl_inc/emailvorlagen_uebersicht.tpl'}
{elseif $step === 'bearbeiten'}
    {include file='tpl_inc/emailvorlagen_bearbeiten.tpl'}
{elseif $step === 'zuruecksetzen'}
    {include file='tpl_inc/emailvorlagen_reset_confirm.tpl'}
{/if}
{include file='tpl_inc/footer.tpl'}
