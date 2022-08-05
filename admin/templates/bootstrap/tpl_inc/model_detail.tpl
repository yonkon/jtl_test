{$description = $description|default:null}
{$enctype = $enctype|default:null}
{$action = $action|default:($shopURL|cat:$smarty.server.PHP_SELF)}
{$method = $method|default:'post'}

{$cancel = $cancel|default:true}
{$delete = $delete|default:false}
{$save = $save|default:true}
{$saveAndContinue = $saveAndContinue|default:false}
{$enable = $enable|default:false}
{$disable = $disable|default:false}

{include file='tpl_inc/seite_header.tpl' cTitel=__('pageTitle') cBeschreibung=$description}

<div id="content">
    <div id="settings">
        <form id="model-detail" name="model_detail" method="{$method}" action="{$action}"{if $enctype !== null} enctype="{$enctype}"{/if}>
            {$jtl_token}
            <input type="hidden" name="id" value="{$item->getId()}" />
            <div class="card">
                <div class="card-header">
                    <div class="subheading1">{__('generalHeading')}</div>
                    <hr class="mb-n3">
                </div>
                <div class="card-body">
                    {include file='tpl_inc/model_item.tpl'}
                </div>
            </div>
            <div class="card-footer save-wrapper">
                <div class="row first-ml-auto">
                    {if $cancel === true}
                        <div class="col-sm-6 col-xl-auto">
                            <button type="submit" name="go-back" value="1" class="btn btn-outline-primary btn-block" id="go-back">
                                {__('cancelWithIcon')}
                            </button>
                        </div>
                    {/if}
                    {if $saveAndContinue === true}
                        <div class="col-sm-6 col-xl-auto">
                            <button type="submit" name="save-model-continue" value="1" class="btn btn-outline-primary btn-block" id="save-and-continue">
                                <i class="fal fa-save"></i> {__('saveAndContinue')}
                            </button>
                        </div>
                    {/if}
                    {if $save === true}
                        <div class="col-sm-6 col-xl-auto">
                            <button type="submit" value="1" name="save-model" class="btn btn-primary btn-block">
                                <i class="far fa-save"></i> {__('save')}
                            </button>
                        </div>
                    {/if}
                    {if $delete === true}
                        <div class="col-sm-6 col-xl-auto">
                            <button type="submit" value="1" name="model-delete" class="btn btn-danger btn-block">
                                <i class="far fa-trash-alt"></i> {__('delete')}
                            </button>
                        </div>
                    {/if}
                    {if $disable === true}
                        <div class="col-sm-6 col-xl-auto">
                            <button type="submit" value="1" name="model-disable" class="btn btn-warning btn-block">
                                <i class="fa fa-close"></i> {__('disable')}
                            </button>
                        </div>
                    {/if}
                    {if $enable === true}
                        <div class="col-sm-6 col-xl-auto">
                            <button type="submit" value="1" name="model-enable" class="btn btn-primary btn-block">
                                <i class="fa fa-check"></i> {__('enable')}
                            </button>
                        </div>
                    {/if}
                </div>
            </div>
        </form>
    </div>
</div>
