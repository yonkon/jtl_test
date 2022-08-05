<div id="content">
    <form id="country-update-form" method="post">
        {$jtl_token}
        <input type="hidden" name="action" value="{$step}" />
        <input type="hidden" name="save" value="1" />
        <div class="card">
            <div class="card-header">
                <div class="subheading1">{if $step === 'update'}{__('updateCountry')}{else}{__('addCountry')}{/if}</div>
                <hr class="mb-n3">
            </div>
            <div class="card-body">
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="cISO">{__('ISO')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input class="form-control"
                               type="text"
                               id="cISO"
                               name="cISO"
                               value="{if isset($countryPost['cISO'])}{$countryPost['cISO']}{elseif !empty($country)}{$country->getISO()}{/if}"
                               tabindex="1"
                               required
                               {if !empty($country)}readonly{/if}
                               maxlength="2"/>
                    </div>
                </div>

                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="cDeutsch">{__('DBcDeutsch')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input class="form-control" type="text" id="cDeutsch" name="cDeutsch" value="{if isset($countryPost['cDeutsch'])}{$countryPost['cDeutsch']}{elseif !empty($country)}{$country->getNameDE()}{/if}" tabindex="2" required/>
                    </div>
                </div>

                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="cEnglisch">{__('DBcEnglisch')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <input class="form-control" type="text" id="cEnglisch" name="cEnglisch" value="{if isset($countryPost['cEnglisch'])}{$countryPost['cEnglisch']}{elseif !empty($country)}{$country->getNameEN()}{/if}" tabindex="3" required/>
                    </div>
                </div>

                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="nEU">{__('isEU')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select name="nEU" id="nEU" class="custom-select" tabindex="4">
                            {$eu = "{if (isset($countryPost['nEU']) && $countryPost['nEU'] === '1') || (!empty($country) && $country->isEU())}1{else}0{/if}"}
                            <option value="0" {if $eu === '0'}selected{/if}>{__('no')}</option>
                            <option value="1" {if $eu === '1'}selected{/if}>{__('yes')}</option>
                        </select>
                    </div>
                </div>

                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="cKontinent">{__('Continent')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select name="cKontinent" id="cKontinent" class="custom-select" tabindex="4">
                            {$currentContinent = "{if isset($countryPost['cKontinent'])}{$countryPost['cKontinent']}{elseif !empty($country)}{$country->getContinent()}{else}{/if}"}
                            {foreach $continents as $continent}
                                <option value="{$continent}" {if $currentContinent === $continent}selected{/if}>
                                    {__($continent)}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="bPermitRegistration">{__('isPermitRegistration')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select name="bPermitRegistration" id="bPermitRegistration" class="custom-select" tabindex="5">
                            {$permitRegistration = "{if (isset($countryPost['bPermitRegistration']) && $countryPost['bPermitRegistration'] === '1') || (!empty($country) && $country->isPermitRegistration())}1{else}0{/if}"}
                            <option value="0" {if $permitRegistration === '0'}selected{/if}>{__('no')}</option>
                            <option value="1" {if $permitRegistration === '1'}selected{/if}>{__('yes')}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group form-row align-items-center">
                    <label class="col col-sm-4 col-form-label text-sm-right" for="bRequireStateDefinition">{__('isRequireStateDefinition')}:</label>
                    <div class="col-sm pl-sm-3 pr-sm-5 order-last order-sm-2">
                        <select name="bRequireStateDefinition" id="bRequireStateDefinition" class="custom-select" tabindex="6">
                            {$requireStateDefinition = "{if (isset($countryPost['bRequireStateDefinition']) && $countryPost['bRequireStateDefinition'] === '1') || (!empty($country) && $country->isRequireStateDefinition())}1{else}0{/if}"}
                            <option value="0" {if $requireStateDefinition === '0'}selected{/if}>{__('no')}</option>
                            <option value="1" {if $requireStateDefinition === '1'}selected{/if}>{__('yes')}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer save-wrapper">
                <div class="row">
                    <div class="ml-auto col-sm-6 col-lg-auto mb-2">
                        <a class="btn btn-outline-primary btn-block" href="countrymanager.php">
                            {__('cancelWithIcon')}
                        </a>
                    </div>
                    <div class="col-sm-6 col-lg-auto ">
                        <button type="submit" class="btn btn-primary btn-block">
                            {__('saveWithIcon')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
