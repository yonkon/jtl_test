<div id="content">
    <div class="card">
        <div class="card-body table-responsive ">
            <table class="table table-align-top table-sticky-head">
                <thead>
                    <tr>
                        <th>{__('ISO')}</th>
                        <th>{__('Name')}</th>
                        <th>{__('DBcDeutsch')}</th>
                        <th>{__('DBcEnglisch')}</th>
                        <th>{__('isEU')}</th>
                        <th>{__('Continent')}</th>
                        <th class="text-center">{__('isShippingAvailable')}{getHelpDesc cDesc=__('isShippingAvailableDesc')}</th>
                        <th class="text-center">{__('isPermitRegistration')}{getHelpDesc cDesc=__('isPermitRegistrationDesc')}</th>
                        <th class="text-center">{__('isRequireStateDefinition')}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                {foreach $countries as $country}
                    <tr>
                        <td>
                            {$country->getISO()}
                        </td>
                        <td>
                            {$country->getName()}
                        </td>
                        <td>
                            {$country->getNameDE()}
                        </td>
                        <td>
                            {$country->getNameEN()}
                        </td>
                        <td>
                            {if $country->isEU()}{__('yes')}{else}{__('no')}{/if}
                        </td>
                        <td>
                            {$country->getContinent()}
                        </td>
                        <td class="text-center">
                            {if $country->isShippingAvailable()}
                                <i class="fa fa-check-circle text-success"></i>
                            {else}
                                <i class="fa fa-times-circle text-danger"></i>
                            {/if}
                        </td>
                        <td class="text-center">
                            {if $country->isPermitRegistration()}{__('yes')}{else}{__('no')}{/if}
                        </td>

                        <td class="text-center">
                            {if $country->isRequireStateDefinition()}{__('yes')}{else}{__('no')}{/if}
                        </td>
                        <td>
                            <form method="post">
                                {$jtl_token}
                                <input type="hidden" name="cISO" value="{$country->getISO()}">
                                <div class="btn-group">
                                    <button type="submit"
                                            name="action"
                                            value="delete"
                                            class="btn btn-link px-2 delete-confirm"
                                            title="{__('delete')}"
                                            data-toggle="tooltip"
                                            data-modal-body="{__('confirmDeleteCountry')|sprintf:$country->getName():$country->getISO()}">
                                        <span class="icon-hover">
                                            <span class="fal fa-trash-alt"></span>
                                            <span class="fas fa-trash-alt"></span>
                                        </span>
                                    </button>
                                    <button name="action"
                                            value="update"
                                            class="btn btn-link px-2"
                                            title="{__('edit')}"
                                            data-toggle="tooltip">
                                        <span class="icon-hover">
                                            <span class="fal fa-edit"></span>
                                            <span class="fas fa-edit"></span>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="card-footer save-wrapper">
            <div class="row">
                <div class="col-sm-6 col-xl-auto">
                    <form id="add-country" method="post">
                        {$jtl_token}
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn btn-primary btn-block" title="{__('create')}">
                            <i class="fa fa-share"></i> {__('create')}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
