<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:11:13
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\widgets\clock.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16f1ead8a2_36629068',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f36ce2e9e41dc92b1ec59cb10dea29a77fcac3e2' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\widgets\\clock.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed16f1ead8a2_36629068 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
 type="text/javascript" src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
js/jquery.jclock.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript">
    $(function ($) {
        $('#clock_time').jclock({
            format: '%H:%M:%S',
        });
    });
    
        $(document).ready(function(){
            var dateLong = new Date();
            var dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            $('#clock_date').html(dateLong.toLocaleDateString('<?php echo $_smarty_tpl->tpl_vars['language']->value;?>
', dateOptions));
        });
    
<?php echo '</script'; ?>
>
<div class="widget-custom-data nospacing">
    <div class="clock">
        <p id="clock_time"></p>
        <p id="clock_date"></p>
    </div>
</div>
<?php }
}
