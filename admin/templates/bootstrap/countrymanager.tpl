{include file='tpl_inc/header.tpl'}

{include file='tpl_inc/seite_header.tpl' cTitel=__('countryManager') cBeschreibung=__('countryManagerDesc') cDokuURL=__('countryManagerURL')}

{if $step === 'overview'}
    {include file='tpl_inc/countrymanager_overview.tpl'}
{elseif $step === 'update' || $step === 'add'}
    {include file='tpl_inc/countrymanager_update.tpl'}
{/if}

{include file='tpl_inc/footer.tpl'}
