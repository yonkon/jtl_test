<hr>
<div class="card">
    <div class="card-header">
        <div class="subheading1">{__('revisions')}</div>
        <hr class="mb-n3">
    </div>
    <div class="card-body">
        {if $revisions|count > 0}
            {if !empty($data)}
                {if $secondary === true}
                    {foreach $data as $foreignKey => $localized}
                        {foreach $show as $attribute}
                            <div class="d-none" id="original-{$attribute|escape}-{$foreignKey}">{if isset($localized->$attribute)}{$localized->$attribute|escape}{elseif is_string($localized)}{$localized|escape}{/if}</div>
                        {/foreach}
                    {/foreach}
                {else}
                    {foreach $show as $attribute}
                        <div class="d-none original" id="original-{$attribute|escape}" data-references="{$attribute|escape}">{$data->$attribute|escape}</div>
                    {/foreach}
                {/if}
            {/if}
            <div id="accordion" role="tablist" aria-multiselectable="true">
                {foreach $revisions as $revision}
                    <div class="card mb-2">
                        <div class="card-header py-2" role="tab" data-idx="{$revision@iteration}" id="heading-revision-{$revision@iteration}">
                            <a class="align-items-center text-decoration-none" data-toggle="collapse" data-parent="#accordion" href="#revision-{$revision@iteration}" aria-expanded="true" aria-controls="profile-{$revision@iteration}">
                                <span class="badge">{$revision->timestamp}</span>
                                <span> | {$revision->author}</span>
                                <i class="fas fa-plus float-right"></i>
                            </a>
                        </div>
                        <div id="revision-{$revision@iteration}" data-idx="{$revision@iteration}" class="collapse" role="tabpanel" aria-labelledby="heading-revision-{$revision@iteration}">
                            <div class="card-body">
                                <div class="list-group revision-content">
                                    {if $secondary === true && isset($revision->content->references)}
                                        {foreach $revision->content->references as $secondaryKey => $ref}
                                            {foreach $show as $attribute}
                                                {if isset($ref->$attribute)}
                                                    <div class="subheading2 mt-4">{$attribute|escape} ({$secondaryKey}):</div>
                                                    <div id="diff-{$revision@iteration}-{$attribute|escape}-{$secondaryKey}"></div>
                                                    <div class="d-none" data-references="{$attribute|escape}" data-references-secondary="{$secondaryKey}">{$ref->$attribute|escape}</div>
                                                {/if}
                                            {/foreach}
                                        {/foreach}
                                    {else}
                                        {foreach $show as $attribute}
                                            {if isset($revision->content->$attribute)}
                                                <div class="subheading2 mt-4">{$attribute|escape}</div>
                                                <div id="diff-{$revision@iteration}-{$attribute|escape}"></div>
                                                <div class="d-none" data-references="{$attribute|escape}" data-references-secondary="">{$revision->content->$attribute|escape}</div>
                                            {/if}
                                        {/foreach}
                                    {/if}
                                </div>
                            </div>
                            <div class="card-footer">
                                <form class="restore-revision" method="post">
                                    {$jtl_token}
                                    <input type="hidden" value="{$revision->id}" name="revision-id" />
                                    <input type="hidden" value="{$revision->type}" name="revision-type" />
                                    <input type="hidden" value="{if $secondary === true}1{else}0{/if}" name="revision-secondary" />
                                    <div class="row">
                                        <div class="ml-auto col-sm-6 col-xl-auto">
                                            <button type="submit" class="btn btn-danger" name="revision-action" value="delete">
                                                <i class="fas fa-trash-alt"></i> {__('revisionDelete')}
                                            </button>
                                        </div>
                                        <div class="col-sm-6 col-xl-auto">
                                            <button type="submit" class="btn btn-primary" name="revision-action" value="restore">
                                                <i class="fa fa-refresh"></i> {__('revisionRestore')}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="alert alert-info">{__('noRevisions')}</div>
        {/if}
    </div>
</div>
<script src="{$templateBaseURL}js/diff_match_patch.js"></script>
<script src="{$PFAD_CODEMIRROR}addon/merge/merge.js"></script>
<link rel="stylesheet" type="text/css" href="{$PFAD_CODEMIRROR}addon/merge/merge.css" />

{literal}
<script type="text/javascript">

    /**
     * @param target
     * @param original
     * @param modified
     */
    function initUI(target, original, modified) {
        target.innerHTML = '';
        dv = CodeMirror.MergeView(target, {
            highlightDifferences: true,
            collapseIdentical:    false,
            revertButtons:        false,
            value:                modified,
            origLeft:             null,
            orig:                 original,
            lineNumbers:          true,
            mode:                 'smartymixed',
            connect:              null
        });
    }

    /**
     * @param mergeView
     * @returns {number}
     */
    function mergeViewHeight(mergeView) {
        function editorHeight(editor) {
            if (!editor) {
                return 0;
            }
            return editor.getScrollInfo().height;
        }
        return Math.max(editorHeight(mergeView.leftOriginal()),
                editorHeight(mergeView.editor()),
                editorHeight(mergeView.rightOriginal()));
    }

    /**
     * @param mergeView
     */
    function resize(mergeView) {
        var height = mergeViewHeight(mergeView),
            newHeight;
        for(;;) {
            if (mergeView.leftOriginal()) {
                mergeView.leftOriginal().setSize(null, height);
            }
            mergeView.editor().setSize(null, height);
            if (mergeView.rightOriginal()) {
                mergeView.rightOriginal().setSize(null, height);
            }
            newHeight = mergeViewHeight(mergeView);
            if (newHeight >= height) {
                break;
            } else {
                height = newHeight;
            }
        }
        mergeView.wrap.style.height = height + 'px';
    }

    $(document).ready(function () {
        $('.collapse').on('shown.bs.collapse', function (a,b) {
            var id               = $(this).attr('data-idx'),
                collapsedElement = $('#revision-' + id),
                closed           = collapsedElement.hasClass('in'),
                hasDiff          = false,
                revisionContent  = collapsedElement.find('.revision-content .d-none');
            revisionContent.each(function(idx, elem) {
                var jelem,
                    reference,
                    secondary,
                    selector,
                    target,
                    originalSelector;
                jelem     = $(elem);
                reference = jelem.attr('data-references');
                secondary = jelem.attr('data-references-secondary');
                selector  = (typeof secondary !== 'undefined' && secondary !== '' && secondary !== null)
                    ? ('diff-' + id + '-' + reference + '-' + secondary)
                    : ('diff-' + id + '-' + reference);
                target    = document.getElementById(selector);
                originalSelector = (typeof secondary !== 'undefined' && secondary !== '' && secondary !== null)
                    ? ('#original-' + reference + '-' + secondary)
                    : ('#original-' + reference);
                initUI(target, $(originalSelector).text(), jelem.text());
            })
        });
    });
</script>
{/literal}