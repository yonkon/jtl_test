<div id="uninstall-{$context}-modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">{__('deletePluginData')}</h2>
                <button type="button" class="close" data-dismiss="modal">
                    <i class="fal fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>{__('deletePluginDataInfo')}</p>
                <div class="custom-control custom-checkbox">
                    <input class="custom-control-input" name="delete-data" type="checkbox" id="delete-data-{$context}" checked>
                    <label class="custom-control-label" for="delete-data-{$context}">{__('deletePluginDataQuestion')}</label>
                    {getHelpDesc cDesc=__('deletePluginDataQuestionDesc')}
                </div>
                <div class="custom-control custom-checkbox">
                    <input class="custom-control-input" name="delete-files" type="checkbox" id="delete-files-{$context}">
                    <label class="custom-control-label" for="delete-files-{$context}">{__('deletePluginFilesQuestion')}</label>
                    {getHelpDesc cDesc=__('deletePluginFilesQuestionDesc')}
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="ml-auto col-sm-6 col-xl-auto submit">
                        <button type="button" class="delete-plugindata-yes btn btn-danger btn-bock">
                            <i class="fa fa-trash-alt"></i>&nbsp;{__('deletePluginDataYes')}
                        </button>
                    </div>
                    <div class="col-sm-6 col-xl-auto submit">
                        <button type="button" class="btn btn-primary" name="cancel" data-dismiss="modal">
                            {__('cancelWithIcon')}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        var disModal = $('#uninstall-{$context}-modal');
        $('{$button}').on('click', function(event) {
            disModal.modal('show');
            return false;
        });
        $('#uninstall-{$context}-modal .delete-plugindata-yes').on('click', function (event) {
            disModal.modal('hide');
            uninstall($('#delete-data-{$context}').is(':checked'));
        });
        function uninstall(deleteData) {
            var data = $('{$selector}').serialize();
            data += '&deinstallieren=1&delete-data=';
            if (deleteData === true) {
                data += '1';
            } else {
                data += '0';
            }
            data += '&delete-files=';
            if (document.getElementById('delete-files-{$context}').checked) {
                data += '1'
            } else {
                data += '0'
            }
            simpleAjaxCall('pluginverwaltung.php', data, function () {
                location.reload();
            });
            return false;
        }
    });
</script>
