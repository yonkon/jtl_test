<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:11:16
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\widgets\patch_data.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16f4bb3c50_37898476',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'bd2062c5f2748889591fa9f0a5f4c3047d500e73' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\widgets\\patch_data.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed16f4bb3c50_37898476 (Smarty_Internal_Template $_smarty_tpl) {
if (count($_smarty_tpl->tpl_vars['oPatch_arr']->value) > 0) {?>
    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['oPatch_arr']->value, 'oPatch');
$_smarty_tpl->tpl_vars['oPatch']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['oPatch']->value) {
$_smarty_tpl->tpl_vars['oPatch']->do_else = false;
?>
        <li>
            <?php if (strlen($_smarty_tpl->tpl_vars['oPatch']->value->cIconURL) > 0) {?>
                <img src="<?php echo urldecode($_smarty_tpl->tpl_vars['oPatch']->value->cIconURL);?>
" alt="" title="<?php echo $_smarty_tpl->tpl_vars['oPatch']->value->cTitle;?>
" />
            <?php }?>
            <p><a href="<?php echo $_smarty_tpl->tpl_vars['oPatch']->value->cURL;?>
" title="<?php echo $_smarty_tpl->tpl_vars['oPatch']->value->cTitle;?>
" target="_blank" rel="noopener">
                <?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'truncate' ][ 0 ], array( $_smarty_tpl->tpl_vars['oPatch']->value->cTitle,50,'...' ));?>

                <?php echo $_smarty_tpl->tpl_vars['oPatch']->value->cDescription;?>

            </a></p>
        </li>
    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
} else { ?>
    <div class="alert alert-info"><?php echo __('noPatchesATM');?>
</div>
<?php }
}
}
