<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:11:14
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\widgets\serversettings.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed16f200c5c3_55185921',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'de470bdbee20deebb96910009fa0da1a78f02ff1' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\widgets\\serversettings.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed16f200c5c3_55185921 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->smarty->ext->_capture->open($_smarty_tpl, 'testfailed', null, null);?>
    <a class="label label-warning" href="systemcheck.php" title="Mehr Informationen im Systemcheck">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i><span class="sr-only"><?php echo __('warning');?>
</span>
    </a>
<?php $_smarty_tpl->smarty->ext->_capture->close($_smarty_tpl);
$_smarty_tpl->smarty->ext->_capture->open($_smarty_tpl, 'testpassed', null, null);?>
    <span class="label label-success">
        <i class="fal fa-check text-success" aria-hidden="true"></i><span class="sr-only"><?php echo __('ok');?>
</span>
    </span>
<?php $_smarty_tpl->smarty->ext->_capture->close($_smarty_tpl);?>

<div class="widget-custom-data">
    <table class="table table-condensed table-hover table-blank">
        <tbody>
            <tr>
                <td><?php echo __('maxPHPExecutionTime');?>
</td>
                <td><?php echo $_smarty_tpl->tpl_vars['maxExecutionTime']->value;?>
</td>
                <td class="text-right">
                    <?php if ($_smarty_tpl->tpl_vars['bMaxExecutionTime']->value) {?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testpassed');?>

                    <?php } else { ?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testfailed');?>

                    <?php }?>
                </td>
            </tr>
            <tr>
                <td><?php echo __('phpMemoryLimit');?>
</td>
                <td><?php echo $_smarty_tpl->tpl_vars['memoryLimit']->value;?>
</td>
                <td class="text-right">
                    <?php if ($_smarty_tpl->tpl_vars['bMemoryLimit']->value) {?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testpassed');?>

                    <?php } else { ?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testfailed');?>

                    <?php }?>
                </td>
            </tr>
            <tr>
                <td><?php echo __('phpMaxTransimissionSizeFile');?>
</td>
                <td><?php echo $_smarty_tpl->tpl_vars['maxFilesize']->value;?>
</td>
                <td class="text-right">
                    <?php if ($_smarty_tpl->tpl_vars['bMaxFilesize']->value) {?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testpassed');?>

                    <?php } else { ?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testfailed');?>

                    <?php }?>
                </td>
            </tr>
            <tr>
                <td><?php echo __('phpMaxTransimissionSizePost');?>
</td>
                <td><?php echo $_smarty_tpl->tpl_vars['postMaxSize']->value;?>
</td>
                <td class="text-right">
                    <?php if ($_smarty_tpl->tpl_vars['bPostMaxSize']->value) {?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testpassed');?>

                    <?php } else { ?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testfailed');?>

                    <?php }?>
                </td>
            </tr>
            <tr>
                <td>allow_url_fopen <?php echo __('activated');?>
</td>
                <td><?php if ($_smarty_tpl->tpl_vars['bAllowUrlFopen']->value) {
echo __('yes');
} else {
echo __('no');
}?></td>
                <td class="text-right">
                    <?php if ($_smarty_tpl->tpl_vars['bAllowUrlFopen']->value) {?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testpassed');?>

                    <?php } else { ?>
                        <?php echo $_smarty_tpl->smarty->ext->_capture->getBuffer($_smarty_tpl, 'testfailed');?>

                    <?php }?>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php }
}
