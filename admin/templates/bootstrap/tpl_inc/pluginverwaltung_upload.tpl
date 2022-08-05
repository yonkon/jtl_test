<div class="tab-pane fade" id="upload">
    <form enctype="multipart/form-data">
        {$jtl_token}
        <div class="form-group">
            {include file='tpl_inc/fileupload.tpl'
            fileID='plugin-install-upload'
            fileUploadUrl="{$adminURL}/pluginverwaltung.php"
            fileBrowseClear=true
            fileUploadAsync=true
            fileAllowedExtensions="['zip']"
            fileMaxSize=100000
            fileOverwriteInitial=false
            fileShowUpload=true
            fileShowRemove=true
            fileDefaultBatchSelectedEvent=false
            fileSuccessMsg="{__('successPluginUpload')}"
            fileErrorMsg="{__('errorPluginUpload')}"
            }
        </div>
        <hr>
    </form>

    <script>
        let $fi = $('#plugin-install-upload');
        {literal}
        $fi.on('fileuploaded', function(event, data, previewId, index) {
            let response = data.response;
            if (response.status === 'OK') {
                if (typeof vLicenses !== 'undefined' && typeof response.license !== 'undefined' && response.license !== null) {
                    vLicenses[response.dir_name.replace('/', '')] = response.license;
                }
                let wasActiveVerfuegbar = $('#verfuegbar').hasClass('active'),
                    wasActiveFehlerhaft = $('#fehlerhaft').hasClass('active');
                $('#verfuegbar').replaceWith(response.html.available);
                $('#fehlerhaft').replaceWith(response.html.erroneous);
                $('a[href="#fehlerhaft"]').find('.badge').html(response.html.erroneous_count);
                $('a[href="#verfuegbar"]').find('.badge').html(response.html.available_count);
                if (wasActiveFehlerhaft) {
                    $('#fehlerhaft').addClass('active show');
                } else if (wasActiveVerfuegbar) {
                    $('#verfuegbar').addClass('active show');
                }
            }
            $fi.fileinput('reset');
            $fi.fileinput('clear');
            $fi.fileinput('refresh');
            $fi.fileinput('enable');
        });
        {/literal}
    </script>
</div>
