{if $account->oGroup->kAdminlogingruppe === 1}
    <button class="btn btn-link px-1 py-0 setting-changelog"
        title="{__('settingLogTitle')}"
        data-toggle="tooltip"
        data-placement="top"
        data-setting-name="{$cnf->cWertName}"
        data-name="{$cnf->cName}"
        data-id="{$cnf->kEinstellungenConf}"
        type="button"
    >
        <span class="icon-hover">
            <span class="fal fa-history"></span>
            <span class="fas fa-history"></span>
        </span>
    </button>
{/if}
