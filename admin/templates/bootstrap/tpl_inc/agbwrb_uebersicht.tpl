{include file='tpl_inc/seite_header.tpl' cTitel=__('agbwrb') cDokuURL=__('agbwrbURL')}
<div id="content" class="row mr-0">
    <div class="{if $recommendations->getRecommendations()->isNotEmpty()}col-md-7{else}col-lg-9 col-xl-7{/if} pr-0 pr-md-4">
        <div class="card">
            <div class="card-body">
                {include file='tpl_inc/language_switcher.tpl'}
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="subheading1">{__('availableAGBWRB')}</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th class="text-left">{__('customerGroup')}</th>
                            <th class="text-center">{__('action')}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $customerGroups as $customerGroup}
                            <tr>
                                <td class="">{$customerGroup->getName()}</td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="agbwrb.php?agbwrb=1&agbwrb_edit=1&kKundengruppe={$customerGroup->getID()}&token={$smarty.session.jtl_token}"
                                           class="btn btn-link px-2"
                                           title="{__('modify')}"
                                           data-toggle="tooltip">
                                            <span class="icon-hover">
                                                <span class="fal fa-edit"></span>
                                                <span class="fas fa-edit"></span>
                                            </span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {if $recommendations->getRecommendations()->isNotEmpty()}
        {include file='tpl_inc/recommendations.tpl' recommendations=$recommendations}
    {/if}
</div>
