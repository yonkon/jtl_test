<div class="card" id="upload">
    <div class="card-header">{__('uploadTemplateHeading')}</div>
    <div class="card-body">
        <form enctype="multipart/form-data">
            {$jtl_token}
            <div class="form-group">
                {include file='tpl_inc/fileupload.tpl'
                fileID='template-install-upload'
                fileUploadUrl="{$adminURL}/shoptemplate.php"
                fileBrowseClear=true
                fileUploadAsync=true
                fileAllowedExtensions="['zip']"
                fileMaxSize=100000
                fileOverwriteInitial=false
                filePreview=false
                fileShowUpload=true
                fileShowRemove=true
                fileDefaultBatchSelectedEvent=false
                fileSuccessMsg="{__('successTemplateUpload')}"
                fileErrorMsg="{__('errorTemplateUpload')}"
                }
            </div>
            <hr>
        </form>
    </div>

    <script>
        let $fi = $('#template-install-upload');
        {literal}
        $fi.on('fileuploaded', function(event, data, previewId, index) {
            let response = data.response;
            if (response.status === 'OK' && response.html) {
                let replace = $(response.html.id);
                if (replace.length > 0) {
                    replace.html(response.html.content);
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
