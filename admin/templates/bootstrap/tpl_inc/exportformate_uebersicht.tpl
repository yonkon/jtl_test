{include file='tpl_inc/seite_header.tpl' cTitel=__('exportformats') cBeschreibung=__('exportformatsDesc') cDokuURL=__('exportformatsURL')}
<div id="content">
    <script type="text/javascript">
        var url     = "{$adminURL}/exportformate.php",
            token   = "{$smarty.session.jtl_token}",
            running = [],
            imgPath = "{$templateBaseURL}gfx/jquery";
        {literal}
        $(function () {
            $('.extract_async').on('click', function (el) {
                init_export(parseInt(el.currentTarget.dataset.exportid, 10));
                return false;
            });
            $('#exportall').on('click', function () {
                $('.extract_async').trigger('click');
                return false;
            });
        });

        function init_export(id) {
            if (running.indexOf(id) !== -1) {
                return false;
            }
            running.push(id);
            show_export_info({kExportformat: id, bFirst: true, nMax: 0, nCurrent: 0});
            $.getJSON(url, {token: token, action: 'export', kExportformat: id, ajax: '1'}, function (cb) {
                do_export(cb);
            });
            return false;
        }

        function do_export(cb) {
            if (typeof cb !== 'object') {
                error_export();
            } else if (cb.bFinished) {
                finish_export(cb);
            } else {
                show_export_info(cb);
                $.getJSON(cb.cURL, {token: token, action: 'export', e: cb.kExportqueue, back: 'admin', ajax: '1', max: cb.nMax}, function (cb) {
                    do_export(cb);
                });
            }
        }

        function error_export(cb) {
            alert('{/literal}{__('errorExport')}{literal}');
        }

        function show_export_info(cb) {
            let elem = $('#progress' + cb.kExportformat);
            elem.find('p').hide();
            elem.find('.export-progress').fadeIn();
            let bar = elem.find('progress');
            if (bar.length === 1) {
                bar.attr('value', cb.nCurrent);
                bar.attr('max', cb.nMax);
                bar.fadeIn();
                elem.find('.from').html(cb.nCurrent);
                elem.find('.to').html(cb.nMax);
            }
        }

        function finish_export(cb) {
            let elem = '#progress' + cb.kExportformat,
                idx  = running.indexOf(cb.kExportformat);
            if (idx > -1) {
                running.splice(idx, 1);
            }
            $(elem).find('.export-progress').fadeOut(250, function () {
                $('#error-msg-' + cb.kExportformat).remove();
                let text  = $(elem).find('p').html(),
                    error = '';
                if (cb.errorMessage.length > 0) {
                    error = '<div class="alert alert-danger" id="error-msg-' + cb.kExportformat + '">' + cb.errorMessage + '</div>';
                }
                $(elem).find('p').html(text).append(error).fadeIn(1000);
            });
            if (typeof cb.lastCreated !== 'undefined') {
                let dt = $('#data-last-created' + cb.kExportformat);
                if (dt.length === 1) {
                    dt.html(cb.lastCreated);
                }
            }
        }
        {/literal}
    </script>

    <div class="card">
        <div class="card-header">
            <div class="subheading1">{__('availableFormats')}</div>
            <hr class="mb-n3">
        </div>
        <div class="table-responsive card-body">
            <table class="table table-align-top">
                <thead>
                <tr>
                    <th class="text-left">{__('name')}</th>
                    <th class="text-left" style="width:320px">{__('filename')}</th>
                    <th class="text-center">{__('language')}</th>
                    <th class="text-center">{__('currency')}</th>
                    <th class="text-center">{__('customerGroup')}</th>
                    <th class="text-center">{__('lastModified')}</th>
                    <th class="text-center">{__('syntax')}</th>
                    <th class="text-center" style="width:200px">{__('actions')}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $exportformate as $exportformat}
                    {if $exportformat->getIsSpecial() === 0}
                        <tr>
                            <td class="text-left">{$exportformat->getName()}</td>
                            <td class="text-left" id="progress{$exportformat->getId()}">
                                <p>{$exportformat->getFilename()}</p>
                                <div class="export-progress" style="display: none">
                                    <progress id="px-{$exportformat->getId()}" max="100" value="0" style="height: 25px"></progress>
                                    <span class="progress-details" style="vertical-align: top"><span class="from">0</span>/<span class="to">0</span> </span>
                                </div>
                            </td>
                            <td class="text-center">{$exportformat->getLanguage()->getLocalizedName()}</td>
                            <td class="text-center">{$exportformat->getCurrency()->getName()}</td>
                            <td class="text-center">{$exportformat->getCustomerGroup()->getName()}</td>
                            <td class="text-center">
                                <span class="date-last-created" id="data-last-created{$exportformat->getId()}">
                                    {if $exportformat->getDateLastCreated() !== null}{$exportformat->getDateLastCreated()->format('Y-m-d H:i:s')}{else}-{/if}
                                </span>
                            </td>
                            <td class="text-center" id="exFormat_{$exportformat->getId()}">
                                {include file='snippets/exportformat_state.tpl' exportformat=$exportformat}
                            </td>
                            <td class="text-center">
                                <form method="post" action="exportformate.php">
                                    {$jtl_token}
                                    <input type="hidden" name="kExportformat" value="{$exportformat->getId()}" />
                                    <div class="btn-group">
                                        <button type="button" data-id="{$exportformat->getId()}"
                                                class="btn btn-link px-1 btn-syntaxcheck"
                                                title="{__('Check syntax')}"
                                                data-toggle="tooltip"
                                                data-placement="top">
                                            <span class="icon-hover">
                                                <span class="fal fa-check"></span>
                                                <span class="fas fa-check"></span>
                                            </span>
                                        </button>
                                        <button type="submit"
                                                name="action"
                                                value="delete"
                                                class="btn btn-link px-1 remove notext delete-confirm"
                                                title="{__('delete')}"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                data-modal-body="{__('sureDeleteFormat')} ({$exportformat->getName()})">
                                            <span class="icon-hover">
                                                <span class="fal fa-trash-alt"></span>
                                                <span class="fas fa-trash-alt"></span>
                                            </span>
                                        </button>
                                        <button name="action" value="export" class="btn btn-link px-1 extract notext{if !$exportformat->getEnabled()} disabled{/if}"
                                                title="{__('createExportFile')}" data-toggle="tooltip" data-placement="top">
                                            <span class="icon-hover">
                                                <span class="fal fa-plus"></span>
                                                <span class="fas fa-plus"></span>
                                            </span>
                                        </button>
                                        <button name="action" value="download" class="btn btn-link px-1 download notext"
                                                title="{__('download')}" data-toggle="tooltip" data-placement="top">
                                            <span class="icon-hover">
                                                <span class="fal fa-download"></span>
                                                <span class="fas fa-download"></span>
                                            </span>
                                        </button>
                                        {if $exportformat->getAsync() === 1}
                                            <a href="#" class="btn btn-link px-1 extract_async notext{if !$exportformat->getEnabled()} disabled{/if}"
                                               title="{__('createExportFileAsync')}" data-toggle="tooltip"
                                               data-placement="top" data-exportid="{$exportformat->getId()}"
                                               id="start-export-{$exportformat->getId()}">
                                                <span class="icon-hover">
                                                    <span class="fal fa-plus-square"></span>
                                                    <span class="fas fa-plus-square"></span>
                                                </span>
                                            </a>
                                        {/if}
                                        <button name="action" value="view" class="btn btn-link px-1 edit notext"
                                                title="{__('edit')}" data-toggle="tooltip" data-placement="top">
                                            <span class="icon-hover">
                                                <span class="fal fa-edit"></span>
                                                <span class="fas fa-edit"></span>
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    {/if}
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="card-footer save-wrapper">
            <div class="row">
                <div class="ml-auto col-sm-6 col-xl-auto">
                    <a class="btn btn-outline-primary btn-block" href="#" id="syntaxcheckall">
                        <i class="fa fa-check"></i> {__('Check syntax')}
                    </a>
                </div>
                <div class="col-sm-6 col-xl-auto">
                    <a class="btn btn-outline-primary btn-block" href="#" id="exportall">
                        {__('exportAll')}
                    </a>
                </div>
                <div class="col-sm-6 col-xl-auto">
                    <a class="btn btn-primary btn-block" href="{$adminURL}/exportformate.php?action=view&new=true&token={$smarty.session.jtl_token}">
                        <i class="fa fa-share"></i> {__('newExportformat')}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    {literal}
    function updateSyntaxNotify() {
        if (doNotify) {
            window.clearTimeout(doNotify);
        }
        doNotify = window.setTimeout(function () {
            ioCall('notificationAction', ['refresh'], undefined, undefined, undefined, true);
            doNotify = null;
        }, 1500);
    }
    function validateExportFormatSyntax(tplID, massCheck) {
        $('#exFormat_' + tplID).html('<span class="fa fa-spinner fa-spin"></span>');
        simpleAjaxCall('io.php', {
            jtl_token: JTL_TOKEN,
            io : JSON.stringify({
                name: 'exportformatSyntaxCheck',
                params : [tplID]
            })
        }, function (result) {
            if (result.state && result.state !== '') {
                $('#exFormat_' + tplID).html(result.state);
            }
            if (result.message && result.message !== '') {
                createNotify({
                    title: '{/literal}{__('smartySyntaxError')}{literal}',
                    message: result.message,
                }, {
                    allow_dismiss: true,
                    type: 'danger',
                    delay: 0
                });
            } else if (result.result && result.result === 'ok' && !massCheck) {
                createNotify({
                    title: '{/literal}{__('Check syntax')}{literal}',
                    message: '{/literal}{__('Smarty syntax ok')}{literal}',
                }, {
                    allow_dismiss: true,
                    type: 'success',
                    delay: 1500
                });
            }
            updateSyntaxNotify();
        }, function (result) {
            $('#exFormat_' + tplID).html('<span class="label text-warning">{/literal}{__('untested')}{literal}</span>');
            updateSyntaxNotify();
            if (result.statusText) {
                let msg = result.statusText;
                if (result.responseJSON && result.responseJSON.error.message !== '') {
                    msg += '<br>' + result.responseJSON.error.message;
                }
                createNotify({
                    title: '{/literal}{__('Syntax check fail')}{literal}',
                    message: msg,
                }, {
                    allow_dismiss: true,
                    type: 'warning',
                    delay: 0
                });
            }
        }, undefined, true);
    }
    var doCheckTpl = {/literal}{$checkTemplate|default:0}{literal},
        doNotify = null;
    if (doCheckTpl && doCheckTpl > 0) {
        validateExportFormatSyntax(doCheckTpl);
    }
    $('.btn-syntaxcheck').on('click', function (e) {
        let id = $(this).data('id');
        if (id) {
            validateExportFormatSyntax(id);
        }
    });
    $('#syntaxcheckall').on('click', function (e) {
        $('.btn-syntaxcheck').each(function (e) {
            let id = $(this).data('id');
            if (id) {
                validateExportFormatSyntax(id, true);
            }
        });

        return false;
    })
    {/literal}
</script>
