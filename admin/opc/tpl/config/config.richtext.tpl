<div class='form-group'>
    <label for="config-{$propname}">{$propdesc.label}</label>
    <textarea name="{$propname}" id="textarea-{$propname}" class="form-control" {if $required}required{/if}>{$propval|htmlspecialchars}</textarea>
    <script>
        var adminLang = '{Shop::Container()->getGetText()->getLanguage()}'.toLowerCase();

        if(!CKEDITOR.lang.languages.hasOwnProperty(adminLang)) {
            adminLang = adminLang.split('-')[0]
        }
        
        CKEDITOR.replace(
            'textarea-{$propname}',
            {
                baseFloatZIndex: 9000,
                language: adminLang,
                filebrowserBrowseUrl: 'elfinder.php?ckeditor=1&token=' + JTL_TOKEN + '&mediafilesType=image',
                /* custom config */
                toolbarGroups:[
                    { name: 'clipboard', groups: ['clipboard', 'undo']},
                    { name: 'editing', groups: ['find', 'selection', 'spellchecker']},
                    { name: 'links'},
                    { name: 'insert'},
                    { name: 'forms'},
                    { name: 'tools'},
                    { name: 'document', groups: ['mode', 'document', 'doctools']},
                    { name: 'others'},
                    '/',
                    { name: 'basicstyles', groups: ['basicstyles', 'cleanup']},
                    { name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},
                    { name: 'styles'},
                    { name: 'colors'},
                    { name: 'about'}
                ],
                format_tags:'p;h1;h2;h3;pre',
                removeDialogTabs:'image:advanced;link:upload;image:Upload',
                allowedContent : true,
                htmlEncodeOutput : false,
                basicEntities : false,
                enterMode : CKEDITOR.ENTER_P,
                entities : false,
                entities_latin : false,
                entities_greek : false,
                ignoreEmptyParagraph : false,
                fillEmptyBlocks : false,
                autoParagraph : false,
                removePlugins : 'exportpdf,language,iframe,flash'
                /* custom config end */
            },
        );

        $.each(CKEDITOR.dtd.$removeEmpty, key => {
            CKEDITOR.dtd.$removeEmpty[key] = false;
        });

        opc.once('save-config', () => {
            $('#textarea-{$propname}').val(CKEDITOR.instances['textarea-{$propname}'].getData());
        });
    </script>
</div>
