<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:08:21
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\footer.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed1645804021_73549943',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7f254104ecf39b1eda2f396131179c8dbb3a25a7' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\footer.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed1645804021_73549943 (Smarty_Internal_Template $_smarty_tpl) {
if ($_smarty_tpl->tpl_vars['account']->value) {?>
        <button class="navbar-toggler sidebar-toggler collapsed" type="button" data-toggle="collapse" data-target="#sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="modal" tabindex="-1" role="dialog" id="modal-footer">
            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"></h2>
                        <button type="button" class="close" data-dismiss="modal">
                            <i class="fal fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer"></div>
                </div>
            </div>
        </div>

        <div class="modal" tabindex="-1" role="dialog" id="modal-footer-delete-confirm">
            <div id="modal-footer-delete-confirm-default-title" class="d-none"><?php echo __('defaultDeleteConfirmTitle');?>
</div>
            <div id="modal-footer-delete-confirm-default-submit" class="d-none"><?php echo __('delete');?>
</div>
            <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title"></h2>
                        <button type="button" class="close" data-dismiss="modal">
                            <i class="fal fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body"></div>
                    <div class="modal-footer">
                        <div class="row">
                            <div class="ml-auto col-sm-6 col-xl-auto mb-2">
                                <button type="button" id="modal-footer-delete-confirm-yes" class="btn btn-danger btn-block">
                                    <i class="fas fa-trash-alt"></i> <?php echo __('delete');?>

                                </button>
                            </div>
                            <div class="col-sm-6 col-xl-auto">
                                <button type="button" class="btn btn-outline-primary btn-block" data-dismiss="modal">
                                    <?php echo __('cancelWithIcon');?>

                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        </div>
    </div>
</div>
<?php echo '<script'; ?>
>
    if(typeof CKEDITOR !== 'undefined') {
        CKEDITOR.editorConfig = function(config) {
            config.language = '<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
';
            config.removeDialogTabs = 'link:upload;image:Upload';
            config.defaultLanguage = 'en';
            config.startupMode = '<?php if ((isset($_smarty_tpl->tpl_vars['Einstellungen']->value['global']['admin_ckeditor_mode'])) && $_smarty_tpl->tpl_vars['Einstellungen']->value['global']['admin_ckeditor_mode'] === 'Q') {?>source<?php } else { ?>wysiwyg<?php }?>';
            config.htmlEncodeOutput = false;
            config.basicEntities = false;
            config.htmlEncodeOutput = false;
            config.allowedContent = true;
            config.enterMode = CKEDITOR.ENTER_P;
            config.entities = false;
            config.entities_latin = false;
            config.entities_greek = false;
            config.ignoreEmptyParagraph = false;
            config.filebrowserBrowseUrl      = 'elfinder.php?ckeditor=1&mediafilesType=misc&token=<?php echo $_SESSION['jtl_token'];?>
';
            config.filebrowserImageBrowseUrl = 'elfinder.php?ckeditor=1&mediafilesType=image&token=<?php echo $_SESSION['jtl_token'];?>
';
            config.filebrowserFlashBrowseUrl = 'elfinder.php?ckeditor=1&mediafilesType=video&token=<?php echo $_SESSION['jtl_token'];?>
';
            config.filebrowserUploadUrl      = 'elfinder.php?ckeditor=1&mediafilesType=misc&token=<?php echo $_SESSION['jtl_token'];?>
';
            config.filebrowserImageUploadUrl = 'elfinder.php?ckeditor=1&mediafilesType=image&token=<?php echo $_SESSION['jtl_token'];?>
';
            config.filebrowserFlashUploadUrl = 'elfinder.php?ckeditor=1&mediafilesType=video&token=<?php echo $_SESSION['jtl_token'];?>
';
            config.extraPlugins = 'codemirror';
            config.fillEmptyBlocks = false;
            config.autoParagraph = false;
        };
        CKEDITOR.editorConfig(CKEDITOR.config);
        $.each(CKEDITOR.dtd.$removeEmpty, function (i, value) {
            CKEDITOR.dtd.$removeEmpty[i] = false;
        });
    }
    $('.select2').select2();
    $(function() {
        ioCall('notificationAction', ['update'], undefined, undefined, undefined, true);
    });
<?php echo '</script'; ?>
>

<?php }?>
</body></html>
<?php }
}
