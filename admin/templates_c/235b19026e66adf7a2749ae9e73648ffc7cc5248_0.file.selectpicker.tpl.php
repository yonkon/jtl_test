<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:08:21
  from 'C:\proj\jtl\test\admin\templates\bootstrap\snippets\selectpicker.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16457dbb07_68266317',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '235b19026e66adf7a2749ae9e73648ffc7cc5248' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\snippets\\selectpicker.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed16457dbb07_68266317 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
 type="module">
    
        $('.selectpicker').selectpicker({
            noneSelectedText: '<?php echo __('selectPickerNoneSelectedText');?>
',
            noneResultsText: '<?php echo __('selectPickerNoneResultsText');?>
',
            countSelectedText: '<?php echo __('selectPickerCountSelectedText');?>
',
            maxOptionsText: () => [
                '<?php echo __('selectPickerLimitReached');?>
',
                '<?php echo __('selectPickerGroupLimitReached');?>
',
            ],
            selectAllText: '<?php echo __('selectPickerSelectAllText');?>
',
            deselectAllText: '<?php echo __('selectPickerDeselectAllText');?>
',
            doneButtonText: '<?php echo __('close');?>
',
            style: ''
        });
    
<?php echo '</script'; ?>
>
<?php }
}
