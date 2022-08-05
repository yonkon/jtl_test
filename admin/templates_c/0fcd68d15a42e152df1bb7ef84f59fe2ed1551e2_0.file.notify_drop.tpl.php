<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:08:43
  from 'C:\proj\jtl\test\admin\templates\bootstrap\tpl_inc\notify_drop.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed165b8546e9_07598698',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '0fcd68d15a42e152df1bb7ef84f59fe2ed1551e2' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\tpl_inc\\notify_drop.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_62ed165b8546e9_07598698 (Smarty_Internal_Template $_smarty_tpl) {
if ($_smarty_tpl->tpl_vars['notifications']->value->totalCount() > 0) {?>
    <?php $_smarty_tpl->_assignInScope('notifyTypes', array(0=>'info',1=>'warning',2=>'danger'));?>
    <a href="#" class="nav-link text-primary px-2" data-toggle="dropdown">
        <span class="fa-layers fa-fw has-notify-icon">
            <span class="fas fa-bell"></span>
            <span class="fa-stack">
                <span class="fas fa-circle fa-stack-2x text-<?php echo $_smarty_tpl->tpl_vars['notifyTypes']->value[$_smarty_tpl->tpl_vars['notifications']->value->getHighestType()];?>
"></span>
                <strong class="fa-stack-1x notify-count"><?php echo $_smarty_tpl->tpl_vars['notifications']->value->count();?>
</strong>
            </span>
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg" role="main">
        <div class="dropdown-header subheading1">
            <a href="#"><i data-toggle="tooltip" title="<?php echo __('Refresh all notifications');?>
" class="fa fa-refresh pull-right refresh-notify" aria-hidden="true"></i></a>
            <?php echo __('notificationsHeader');?>

        </div>
        <div class="dropdown-divider"></div>
        <?php $_smarty_tpl->_assignInScope('notifyCount', 0);?>
        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['notifications']->value, 'notify');
$_smarty_tpl->tpl_vars['notify']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['notify']->value) {
$_smarty_tpl->tpl_vars['notify']->do_else = false;
?>
            <?php if (!$_smarty_tpl->tpl_vars['notify']->value->isIgnored()) {?>
                <div<?php if ($_smarty_tpl->tpl_vars['notify']->value->getHash() !== '') {?> id="<?php echo $_smarty_tpl->tpl_vars['notify']->value->getHash();?>
"<?php }?>>
                    <?php if ($_smarty_tpl->tpl_vars['notifyCount']->value++ > 0) {?>
                        <div class="dropdown-divider"></div>
                    <?php }?>
                    <div class="dropdown-header">
                        <?php if ($_smarty_tpl->tpl_vars['notify']->value->getHash() !== null) {?>
                            <button type="button" class="close pull-right close-notify" aria-label="Close" data-hash="<?php echo $_smarty_tpl->tpl_vars['notify']->value->getHash();?>
">
                                <span aria-hidden="true" data-toggle="tooltip" title="<?php echo __('Mark notification as read');?>
">&times;</span>
                            </button>
                        <?php }?>
                        <i class="fa fa-circle text-<?php echo $_smarty_tpl->tpl_vars['notifyTypes']->value[$_smarty_tpl->tpl_vars['notify']->value->getType()];?>
" aria-hidden="true"></i>
                        <?php echo $_smarty_tpl->tpl_vars['notify']->value->getTitle();?>

                    </div>
                    <div class="dropdown-item-text">
                        <?php if ($_smarty_tpl->tpl_vars['notify']->value->getUrl() !== null) {?><a href="<?php echo $_smarty_tpl->tpl_vars['notify']->value->getUrl();?>
"><?php }?>
                            <?php echo $_smarty_tpl->tpl_vars['notify']->value->getDescription();?>

                        <?php if ($_smarty_tpl->tpl_vars['notify']->value->getUrl() !== null) {?></a><?php }?>
                    </div>
                </div>
            <?php }?>
        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        <?php if ($_smarty_tpl->tpl_vars['notifications']->value->count() != $_smarty_tpl->tpl_vars['notifications']->value->totalCount()) {?>
            <?php if ($_smarty_tpl->tpl_vars['notifications']->value->count() > 0) {?>
                <div class="dropdown-divider"></div>
            <?php }?>
            <div class="dropdown-item-text">
                <a href="#" class="showall-notify" data-toggle="tooltip" title="<?php echo __('Mark all notifications as unread');?>
"><?php echo __('showAll');?>
</a>
            </div>
        <?php }?>
    </div>
<?php }
echo '<script'; ?>
>
    
    var notificationModified = false;
    $('#notify-drop')
        .on('click', '.close-notify', function (e) {
            let hash   = $(this).data('hash'),
                $notes = $('.notify-count', '#notify-drop'),
                notes  = parseInt($notes.text());

            notificationModified = true;
            $('[data-toggle="tooltip"]', this).tooltip('hide');
            $('#' + hash).remove();
            ioCall('notificationAction', ['dismiss', hash], undefined, undefined, undefined, true);
            if (--notes > 0) {
                e.stopPropagation();
                $notes.text(notes);
            }
        })
        .on('click', '.refresh-notify', function () {
            ioCall('notificationAction', ['refresh'], undefined, undefined, undefined, true);
        })
        .on('click', '.showall-notify', function () {
            ioCall('notificationAction', ['reset'], undefined, undefined, undefined, true);
        })
        .on('hidden.bs.dropdown', function () {
            if (notificationModified === true) {
                notificationModified = false;
                window.setTimeout(function () {
                    ioCall('notificationAction', ['update'], undefined, undefined, undefined, true);
                }, 150);
            }
        });
    
<?php echo '</script'; ?>
>
<?php }
}
