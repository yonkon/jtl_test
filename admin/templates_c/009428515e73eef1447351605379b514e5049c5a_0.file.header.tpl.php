<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:08:21
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\header.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16457b42d1_35983343',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '009428515e73eef1447351605379b514e5049c5a' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\header.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:snippets/selectpicker.tpl' => 1,
    'file:tpl_inc/backend_sidebar.tpl' => 1,
    'file:tpl_inc/backend_search.tpl' => 1,
    'file:tpl_inc/favs_drop.tpl' => 1,
    'file:tpl_inc/notify_drop.tpl' => 1,
    'file:tpl_inc/updates_drop.tpl' => 1,
    'file:snippets/alert_list.tpl' => 1,
  ),
),false)) {
function content_62ed16457b42d1_35983343 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('bForceFluid', (($tmp = $_smarty_tpl->tpl_vars['bForceFluid']->value ?? null)===null||$tmp==='' ? false : $tmp));?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo __('shopTitle');?>
</title>
    <?php $_smarty_tpl->_assignInScope('urlPostfix', ('?v=').($_smarty_tpl->tpl_vars['adminTplVersion']->value));?>
    <link type="image/x-icon" href="<?php echo $_smarty_tpl->tpl_vars['faviconAdminURL']->value;?>
" rel="icon">
    <?php echo $_smarty_tpl->tpl_vars['admin_css']->value;?>

    <link type="text/css" rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
lib/codemirror.css<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
">
    <link type="text/css" rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/hint/show-hint.css<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
">
    <link type="text/css" rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/display/fullscreen.css<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
">
    <link type="text/css" rel="stylesheet" href="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/scroll/simplescrollbars.css<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
">
    <?php echo $_smarty_tpl->tpl_vars['admin_js']->value;?>

    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CKEDITOR']->value;?>
ckeditor.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
lib/codemirror.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/hint/show-hint.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/hint/sql-hint.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/scroll/simplescrollbars.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
addon/display/fullscreen.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/css/css.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/javascript/javascript.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/xml/xml.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/php/php.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/htmlmixed/htmlmixed.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/smarty/smarty.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/smartymixed/smartymixed.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['PFAD_CODEMIRROR']->value;?>
mode/sql/sql.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
js/codemirror_init.js<?php echo $_smarty_tpl->tpl_vars['urlPostfix']->value;?>
"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
>
        var bootstrapButton = $.fn.button.noConflict();
        $.fn.bootstrapBtn = bootstrapButton;
        setJtlToken('<?php echo $_SESSION['jtl_token'];?>
');

        function switchAdminLang(tag)
        {
            event.target.href = `benutzerverwaltung.php?token=<?php echo $_SESSION['jtl_token'];?>
&action=quick_change_language&language=` + tag + `&referer=` +  encodeURIComponent(window.location.href);
        }
    <?php echo '</script'; ?>
>

    <?php echo '<script'; ?>
 type="text/javascript"
            src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
js/fileinput/locales/<?php echo mb_substr($_smarty_tpl->tpl_vars['language']->value,0,2);?>
.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
 type="module" src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
js/app/app.js"><?php echo '</script'; ?>
>
    <?php $_smarty_tpl->_subTemplateRender('file:snippets/selectpicker.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
</head>
<body>
<?php if ($_smarty_tpl->tpl_vars['account']->value !== false && (isset($_SESSION['loginIsValid'])) && $_SESSION['loginIsValid'] === true) {?>
    <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['getCurrentPage'][0], array( array('assign'=>'currentPage'),$_smarty_tpl ) );?>

    <div class="spinner"></div>
    <div id="page-wrapper" class="backend-wrapper hidden disable-transitions<?php if ($_smarty_tpl->tpl_vars['currentPage']->value === 'index' || $_smarty_tpl->tpl_vars['currentPage']->value === 'status') {?> dashboard<?php }?>">
        <?php if (!$_smarty_tpl->tpl_vars['hasPendingUpdates']->value && $_smarty_tpl->tpl_vars['wizardDone']->value) {?>
            <?php $_smarty_tpl->_subTemplateRender('file:tpl_inc/backend_sidebar.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
        <?php }?>
        <div class="backend-main <?php if (!$_smarty_tpl->tpl_vars['hasPendingUpdates']->value && $_smarty_tpl->tpl_vars['wizardDone']->value) {?>sidebar-offset<?php }?>">
            <?php if ((defined('SAFE_MODE') ? constant('SAFE_MODE') : null)) {?>
            <div class="alert alert-warning fade show" role="alert">
                <i class="fal fa-exclamation-triangle mr-2"></i>
                <?php echo __('Safe mode enabled.');?>

                <a href="./?safemode=off" class="btn btn-light"><span class="fas fa-exclamation-circle mr-0 mr-lg-2"></span><span><?php echo __('deactivate');?>
</span></a>
            </div>
            <?php }?>
            <div id="topbar" class="backend-navbar row mx-0 align-items-center topbar flex-nowrap">
                <?php if (!$_smarty_tpl->tpl_vars['hasPendingUpdates']->value && $_smarty_tpl->tpl_vars['wizardDone']->value) {?>
                <div class="col search px-0 px-md-3">
                    <?php $_smarty_tpl->_subTemplateRender('file:tpl_inc/backend_search.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                </div>
                <?php }?>
                <div class="col-auto ml-auto px-2">
                    <ul class="nav align-items-center">
                        <?php if (!$_smarty_tpl->tpl_vars['hasPendingUpdates']->value && $_smarty_tpl->tpl_vars['wizardDone']->value) {?>
                            <li class="nav-item dropdown mr-md-3" id="favs-drop">
                                <?php $_smarty_tpl->_subTemplateRender("file:tpl_inc/favs_drop.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
                            </li>
                            <li class="nav-item dropdown fa-lg">
                                <a href="#" class="nav-link text-dark-gray px-2" data-toggle="dropdown">
                                    <span class="fal fa-map-marker-question fa-fw"></span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <span class="dropdown-header"><?php echo __('helpCenterHeader');?>
</span>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="https://jtl-url.de/shopschritte" target="_blank" rel="noopener">
                                        <?php echo __('firstSteps');?>

                                    </a>
                                    <a class="dropdown-item" href="https://jtl-url.de/0762z" target="_blank" rel="noopener">
                                        <?php echo __('jtlGuide');?>

                                    </a>
                                    <a class="dropdown-item" href="https://forum.jtl-software.de" target="_blank" rel="noopener">
                                        <?php echo __('jtlForum');?>

                                    </a>
                                    <a class="dropdown-item" href="https://www.jtl-software.de/Training" target="_blank" rel="noopener">
                                        <?php echo __('training');?>

                                    </a>
                                    <a class="dropdown-item" href="https://www.jtl-software.de/Servicepartner" target="_blank" rel="noopener">
                                        <?php echo __('servicePartners');?>

                                    </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown fa-lg" id="notify-drop"><?php $_smarty_tpl->_subTemplateRender("file:tpl_inc/notify_drop.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?></li>
                            <li class="nav-item dropdown fa-lg" id="updates-drop"><?php $_smarty_tpl->_subTemplateRender("file:tpl_inc/updates_drop.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?></li>
                        <?php }?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle parent btn-toggle" data-toggle="dropdown">
                                <i class="fal fa-language d-sm-none"></i> <span class="d-sm-block d-none"><?php echo $_smarty_tpl->tpl_vars['languageName']->value;?>
</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['languages']->value, 'langName', false, 'tag');
$_smarty_tpl->tpl_vars['langName']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['tag']->value => $_smarty_tpl->tpl_vars['langName']->value) {
$_smarty_tpl->tpl_vars['langName']->do_else = false;
?>
                                    <?php if ($_smarty_tpl->tpl_vars['language']->value !== $_smarty_tpl->tpl_vars['tag']->value) {?>
                                        <a class="dropdown-item" onclick="switchAdminLang('<?php echo $_smarty_tpl->tpl_vars['tag']->value;?>
')" href="#">
                                            <?php echo $_smarty_tpl->tpl_vars['langName']->value;?>

                                        </a>
                                    <?php }?>
                                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="col-auto border-left border-dark-gray px-0 px-md-3">
                    <div class="dropdown avatar">
                        <button class="btn btn-link text-decoration-none dropdown-toggle p-0" data-toggle="dropdown">
                            <img src="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['getAvatar'][0], array( array('account'=>$_smarty_tpl->tpl_vars['account']->value),$_smarty_tpl ) );?>
" class="img-circle">
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item link-shop" href="<?php echo $_smarty_tpl->tpl_vars['URL_SHOP']->value;?>
?fromAdmin=yes" title="<?php echo __('goShop');?>
" target="_blank">
                                <i class="fa fa-shopping-cart"></i> <?php echo __('goShop');?>

                            </a>
                            <a class="dropdown-item link-logout" href="logout.php?token=<?php echo $_SESSION['jtl_token'];?>
"
                               title="<?php echo __('logout');?>
">
                                <i class="fa fa-sign-out"></i> <?php echo __('logout');?>

                            </a>
                        </div>
                    </div>
                </div>
                <div class="opaque-background"></div>
            </div>
            <?php if (!$_smarty_tpl->tpl_vars['hasPendingUpdates']->value && $_smarty_tpl->tpl_vars['expiredLicenses']->value->count() > 0) {?>
                <div class="modal fade in" id="expiredLicensesNotice" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="staticBackdropLabel"><?php echo __('Licensing problem detected');?>
</h5>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-2"><i class="fa fa-exclamation-triangle" style="font-size: 8em; padding-bottom:10px; color: red;"></i></div>
                                    <div class="col-md-10 ml-auto">
                                        <strong><?php echo __('No valid licence found for the following installed and active extensions:');?>
</strong>
                                        <?php $_block_plugin1 = isset($_smarty_tpl->smarty->registered_plugins['block']['form'][0][0]) ? $_smarty_tpl->smarty->registered_plugins['block']['form'][0][0] : null;
if (!is_callable(array($_block_plugin1, 'render'))) {
throw new SmartyException('block tag \'form\' not callable or registered');
}
$_smarty_tpl->smarty->_cache['_tag_stack'][] = array('form', array('id'=>"plugins-disable-form"));
$_block_repeat=true;
echo $_block_plugin1->render(array('id'=>"plugins-disable-form"), null, $_smarty_tpl, $_block_repeat);
while ($_block_repeat) {
ob_start();?>
                                            <input type="hidden" name="action" value="disable-expired-plugins">
                                            <ul>
                                                <?php $_smarty_tpl->_assignInScope('hasPlugin', false);?>
                                                <?php $_smarty_tpl->_assignInScope('hasTemplate', false);?>
                                                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['expiredLicenses']->value, 'license');
$_smarty_tpl->tpl_vars['license']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['license']->value) {
$_smarty_tpl->tpl_vars['license']->do_else = false;
?>
                                                    <?php if ($_smarty_tpl->tpl_vars['license']->value->getType() === 'plugin') {?>
                                                        <?php $_smarty_tpl->_assignInScope('hasPlugin', true);?>
                                                    <?php } elseif ($_smarty_tpl->tpl_vars['license']->value->getType() === 'template') {?>
                                                        <?php $_smarty_tpl->_assignInScope('hasTemplate', true);?>
                                                    <?php }?>
                                                    <li><?php echo $_smarty_tpl->tpl_vars['license']->value->getName();?>
</li>
                                                    <input type="hidden" name="pluginID[]" value="<?php echo $_smarty_tpl->tpl_vars['license']->value->getReferencedItem()->getInternalID();?>
">
                                                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                            </ul>
                                        <?php $_block_repeat=false;
echo $_block_plugin1->render(array('id'=>"plugins-disable-form"), ob_get_clean(), $_smarty_tpl, $_block_repeat);
}
array_pop($_smarty_tpl->smarty->_cache['_tag_stack']);?>
                                    </div>
                                </div>
                                <div class="alert alert-secondary" role="alert">
                                    <p><strong><?php echo __('Possible reasons:');?>
</strong></p>
                                    <ul class="small">
                                        <li><?php echo __('The extension was obtained from a different source than the JTL-Extension Store');?>
</li>
                                        <li><?php echo __('The licence is not bound to this shop yet (check licence in "My purchases")');?>
</li>
                                        <li><?php echo __('The licence is bound to a different customer account that is not connected to this shop (check connected account in "My purchases")');?>
</li>
                                        <li><?php echo __('The manufacturer disabled the licence');?>
</li>
                                    </ul>
                                </div>
                                <p><strong><?php echo __('Further use of the extension may constitute a licence violation!');?>
</strong><br>
                                    <?php echo __('Please purchase a licence in the JTL-Extension Store or contact the manufacturer of the extension for information on rights of use.');?>

                                </p>
                            </div>
                            <div class="modal-footer">
                                <input type="checkbox" id="understood-license-notice">
                                <label for="understood-license-notice"><?php echo __('I understood this notice.');?>
</label>
                                <button type="button" class="btn btn-default" disabled data-dismiss="modal" id="licenseUnderstood"><?php echo __('Understood');?>
</button>
                                <?php if ($_smarty_tpl->tpl_vars['hasPlugin']->value === true) {?>
                                    <button type="button" class="btn btn-primary" data-dismiss="modal" id="licenseDisablePlugins"><?php echo __('Disable plugins');?>
</button>
                                <?php }?>
                                <?php if ($_smarty_tpl->tpl_vars['hasTemplate']->value === true) {?>
                                    <button type="button" class="btn btn-primary" data-dismiss="modal" id="licenseGotoTemplates"><?php echo __('Disable template');?>
</button>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo '<script'; ?>
>
                    $(document).ready(function() {
                        $('#expiredLicensesNotice').modal('show');
                        $('#understood-license-notice').on('click', function (e) {
                            $('#licenseUnderstood').attr('disabled', false);
                        });
                        $('#licenseUnderstood').on('click', function (e) {
                            var newURL = new URL(window.location.href);
                            newURL.searchParams.append('licensenoticeaccepted', 'true');
                            window.location.href = newURL.toString();
                            return true;
                        });
                        $('#licenseDisablePlugins').on('click', function (e) {
                            $('#plugins-disable-form').submit();
                            return true;
                        });
                        $('#licenseGotoTemplates').on('click', function (e) {
                            window.location.href = '<?php echo $_smarty_tpl->tpl_vars['shopURL']->value;?>
/<?php echo (defined('PFAD_ADMIN') ? constant('PFAD_ADMIN') : null);?>
shoptemplate.php?licensenoticeaccepted=true';
                            return true;
                        });
                    });
                <?php echo '</script'; ?>
>
            <?php }?>
            <div class="backend-content" id="content_wrapper">

            <?php $_smarty_tpl->_subTemplateRender('file:snippets/alert_list.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
}
