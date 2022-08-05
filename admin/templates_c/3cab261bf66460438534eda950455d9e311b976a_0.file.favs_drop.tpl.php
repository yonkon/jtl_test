<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:11:14
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\favs_drop.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16f21bec06_39511142',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3cab261bf66460438534eda950455d9e311b976a' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\favs_drop.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed16f21bec06_39511142 (Smarty_Internal_Template $_smarty_tpl) {
?><a href="#" class="btn btn-primary favorites dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
    <span class="fas fa-star mr-0 mr-lg-2"></span>
    <span class="d-none d-lg-inline-block"><?php echo __('favorites');?>
</span>
</a>
<div class="dropdown-menu dropdown-menu-right" role="main">
    <?php if ((isset($_smarty_tpl->tpl_vars['favorites']->value)) && is_array($_smarty_tpl->tpl_vars['favorites']->value) && count($_smarty_tpl->tpl_vars['favorites']->value) > 0) {?>
        <span class="dropdown-header"><?php echo __('favouritesHeader');?>
</span>
        <div class="dropdown-divider"></div>
        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['favorites']->value, 'favorite');
$_smarty_tpl->tpl_vars['favorite']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['favorite']->value) {
$_smarty_tpl->tpl_vars['favorite']->do_else = false;
?>
            <a class="dropdown-item" href="<?php echo $_smarty_tpl->tpl_vars['favorite']->value->cAbsUrl;?>
" rel="<?php echo $_smarty_tpl->tpl_vars['favorite']->value->kAdminfav;?>
"<?php if ($_smarty_tpl->tpl_vars['favorite']->value->bExtern) {?> target="_blank"<?php }?>><?php echo $_smarty_tpl->tpl_vars['favorite']->value->cTitel;
if ($_smarty_tpl->tpl_vars['favorite']->value->bExtern) {?> <i class="fa fa-external-link"></i><?php }?></a>
        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>

        <div class="dropdown-divider"></div>
    <?php }?>
    <a class="dropdown-item" href="favs.php"><i class="fa fa-pencil mr-1"></i> <?php echo __('manageFavorites');?>
</a>
</div><?php }
}
