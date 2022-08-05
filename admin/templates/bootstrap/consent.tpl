{include file='tpl_inc/header.tpl'}

{if $step === 'overview'}
    {include file='tpl_inc/model_list.tpl' items=$models select=true edit=true search=true delete=false disable=true enable=true}
{elseif $step === 'detail'}
    {include file='tpl_inc/model_detail.tpl' item=$item saveAndContinue=true save=true cancel=true}
{/if}

{include file='tpl_inc/footer.tpl'}
