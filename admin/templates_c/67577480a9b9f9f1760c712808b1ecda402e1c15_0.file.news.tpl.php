<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:11:13
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\widgets\news.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16f1d6e020_51720336',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '67577480a9b9f9f1760c712808b1ecda402e1c15' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\widgets\\news.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed16f1d6e020_51720336 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
 type="text/javascript">
    $(document).ready(function () {
        ioCall(
            'getRemoteData',
            ['<?php echo (defined('JTLURL_GET_SHOPNEWS') ? constant('JTLURL_GET_SHOPNEWS') : null);?>
',
                'oNews_arr',
                'widgets/news_data.tpl',
                'news_data_wrapper'],
            undefined,
            undefined,
            undefined,
            true
        );
    });
<?php echo '</script'; ?>
>

<div class="widget-custom-data">
    <div id="news_data_wrapper">
        <p class="ajax_preloader"><i class="fa fas fa-spinner fa-spin"></i> <?php echo __('loading');?>
</p>
    </div>
</div>
<?php }
}
