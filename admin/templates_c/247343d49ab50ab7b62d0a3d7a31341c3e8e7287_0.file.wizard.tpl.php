<?php
/* Smarty version 3.1.39, created on 2022-08-05 15:08:30
  from 'C:\proj\jtl\test\admin\templates\bootstrap\wizard.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.39',
  'unifunc' => 'content_62ed164ea16845_16339443',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '247343d49ab50ab7b62d0a3d7a31341c3e8e7287' => 
    array (
      0 => 'C:\\proj\\jtl\\test\\admin\\templates\\bootstrap\\wizard.tpl',
      1 => 1643806417,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:tpl_inc/header.tpl' => 1,
    'file:tpl_inc/seite_header.tpl' => 1,
    'file:tpl_inc/wizard_question.tpl' => 1,
    'file:tpl_inc/footer.tpl' => 1,
  ),
),false)) {
function content_62ed164ea16845_16339443 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender('file:tpl_inc/header.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
$_smarty_tpl->smarty->ext->configLoad->_loadConfigFile($_smarty_tpl, ((string)$_smarty_tpl->tpl_vars['lang']->value).".conf", 'shopsitemap', 0);
?>

<?php $_smarty_tpl->_subTemplateRender('file:tpl_inc/seite_header.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('cTitel'=>__('setupAssistant'),'cBeschreibung'=>__('setupAssistantDesc'),'cDokuURL'=>__('setupAssistantURL')), 0, false);
echo '<script'; ?>
 type="text/javascript">
    $(window).on('load',function(){
        $('#modal-setup-assistant').modal('show');
    });
<?php echo '</script'; ?>
>
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal-setup-assistant">
    <?php echo __('launchSetup');?>

</button>
<div class="modal fade"
     id="modal-setup-assistant"
     tabindex="-1"
     role="dialog"
     aria-labelledby="modal-setup-assistantTitle"
     aria-hidden="true"
     data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <form method="post">
            <?php echo $_smarty_tpl->tpl_vars['jtl_token']->value;?>

            <input id="has-auth" type="hidden" value="<?php if ($_smarty_tpl->tpl_vars['hasAuth']->value) {?>true<?php } else { ?>false<?php }?>" disabled/>
            <input id="auth-redirect" type="hidden" value="<?php echo (($tmp = $_smarty_tpl->tpl_vars['authRedirect']->value ?? null)===null||$tmp==='' ? false : $tmp);?>
" disabled/>
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span class="fal fa-times"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
gfx/JTL-Shop-Logo-rgb.png" width="101" height="32" alt="JTL-Shop">
                    <span class="h1 mt-3"><?php echo __('setupAssistant');?>
</span>

                    <div class="setup-steps steps">
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['steps']->value, 'step');
$_smarty_tpl->tpl_vars['step']->index = -1;
$_smarty_tpl->tpl_vars['step']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['step']->value) {
$_smarty_tpl->tpl_vars['step']->do_else = false;
$_smarty_tpl->tpl_vars['step']->index++;
$__foreach_step_0_saved = $_smarty_tpl->tpl_vars['step'];
?>
                            <?php $_smarty_tpl->_assignInScope('stepID', $_smarty_tpl->tpl_vars['step']->index+1);?>
                            <div class="step" data-setup-step="<?php echo $_smarty_tpl->tpl_vars['stepID']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['stepID']->value;?>
</div>
                        <?php
$_smarty_tpl->tpl_vars['step'] = $__foreach_step_0_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                        <div class="step" data-setup-step="<?php echo $_smarty_tpl->tpl_vars['stepID']->value+1;?>
"><?php echo $_smarty_tpl->tpl_vars['stepID']->value+1;?>
</div>
                    </div>

                    <div class="setup-slide row align-items-center" data-setup-slide="0">
                        <div class="col-md-6 col-lg-4">
                            <span class="setup-subheadline"><?php echo __('welcome');?>
</span>
                            <p><?php echo __('welcomeDesc');?>
</p>
                            <?php if (!$_smarty_tpl->tpl_vars['hasAuth']->value) {?>
                                <a href="wizard.php?action=auth&wizard-authenticated=1" class="btn btn-primary mt-5 mb-3 px-3 w-100">
                                    <?php echo __('beginAuth');?>

                                </a>
                                <button type="button" class="btn btn-outline-primary px-3 w-100" data-setup-next>
                                    <?php echo __('beginNoAuth');?>

                                </button>
                                <p class="subheading1 mt-4">
                                    <?php echo __('oAuthInformation');?>

                                    <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['getHelpDesc'][0], array( array('cDesc'=>__('oAuthDesc'),'iconQuestion'=>true),$_smarty_tpl ) );?>

                                </p>
                            <?php } else { ?>
                                <button type="button" class="btn btn-primary min-w-sm mt-5 mt-lg-7" data-setup-next>
                                   <?php echo __('beginSetup');?>

                                </button>
                            <?php }?>
                        </div>
                        <div class="col-md-6 mx-md-auto col-xl-5 d-none d-md-block text-center">
                            <img class="img-fluid" src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
img/setup-assistant-roboter.svg" width="416" height="216" alt="<?php echo __('setupAssistant');?>
">
                        </div>
                    </div>
                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['steps']->value, 'step');
$_smarty_tpl->tpl_vars['step']->index = -1;
$_smarty_tpl->tpl_vars['step']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['step']->value) {
$_smarty_tpl->tpl_vars['step']->do_else = false;
$_smarty_tpl->tpl_vars['step']->index++;
$__foreach_step_1_saved = $_smarty_tpl->tpl_vars['step'];
?>
                        <div id="<?php echo $_smarty_tpl->tpl_vars['step']->value->getID();?>
" class="setup-slide row" data-setup-slide="<?php echo $_smarty_tpl->tpl_vars['step']->value->getID();?>
">
                            <div class="col-lg-4 mb-5 mb-lg-0">
                                <?php if ($_smarty_tpl->tpl_vars['authRedirect']->value && $_smarty_tpl->tpl_vars['step']->value->getID() === 1) {?>
                                    <div class="mt-6 mb-lg-n5">
                                        <div class="icon-in-border">
                                            <div class="subheading1"><?php echo __('oAuthValid');?>
</div>
                                            <span class="text-success">
                                                <i class="fas fa-user"></i>
                                                <i class="fas fa-check font-size-sm"></i>
                                            </span>
                                        </div>
                                    </div>
                                <?php }?>
                                <span class="setup-subheadline"><?php echo $_smarty_tpl->tpl_vars['step']->value->getTitle();?>
</span>
                                <p><?php echo $_smarty_tpl->tpl_vars['step']->value->getDescription();?>
</p>
                            </div>
                            <div class="col-lg-6 ml-lg-auto col-xl-7 mt-lg-n5">
                                <div class="row">
                                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['step']->value->getQuestions(), 'question');
$_smarty_tpl->tpl_vars['question']->index = -1;
$_smarty_tpl->tpl_vars['question']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['question']->value) {
$_smarty_tpl->tpl_vars['question']->do_else = false;
$_smarty_tpl->tpl_vars['question']->index++;
$_smarty_tpl->tpl_vars['question']->first = !$_smarty_tpl->tpl_vars['question']->index;
$__foreach_question_2_saved = $_smarty_tpl->tpl_vars['question'];
?>
                                        <?php if ($_smarty_tpl->tpl_vars['question']->value->getSubheading() !== null) {?>
                                            <div class="col-12">
                                                <span class="subheading1 form-title">
                                                    <?php echo $_smarty_tpl->tpl_vars['question']->value->getSubheading();?>

                                                    <?php if ($_smarty_tpl->tpl_vars['question']->value->getSubheadingDescription() !== null) {?>
                                                        <span class="fal fa-info-circle text-muted ml-4" data-toggle="tooltip" title="<?php echo $_smarty_tpl->tpl_vars['question']->value->getSubheadingDescription();?>
"></span>
                                                    <?php }?>
                                                </span>
                                            </div>
                                        <?php }?>
                                        <?php $_smarty_tpl->_subTemplateRender('file:tpl_inc/wizard_question.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('question'=>$_smarty_tpl->tpl_vars['question']->value), 0, true);
?>
                                    <?php
$_smarty_tpl->tpl_vars['question'] = $__foreach_question_2_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                </div>
                            </div>
                        </div>
                    <?php
$_smarty_tpl->tpl_vars['step'] = $__foreach_step_1_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>

                    <div class="setup-slide row" data-setup-slide="<?php echo $_smarty_tpl->tpl_vars['stepID']->value+1;?>
">
                        <div class="col-lg-4 mb-5 mb-lg-0">
                            <?php if ($_smarty_tpl->tpl_vars['authRedirect']->value) {?>
                                <div class="mt-6 mb-lg-n5">
                                    <div class="icon-in-border">
                                        <div class="subheading1"><?php echo __('oAuthValid');?>
</div>
                                        <span class="text-success">
                                            <i class="fas fa-user"></i>
                                            <i class="fas fa-check font-size-sm"></i>
                                        </span>
                                    </div>
                                </div>
                            <?php }?>
                            <span class="setup-subheadline"><?php echo __('stepFive');?>
</span>
                            <?php if (!$_smarty_tpl->tpl_vars['hasAuth']->value) {?>
                                <div id="summary-plugin-note">
                                    <i class="fal fa-exclamation-triangle text-warning"></i> <?php echo __('oAuthInvalid');?>

                                </div>
                            <?php }?>
                            <p><?php echo __('stepFiveDesc');?>
</p>
                        </div>
                        <div class="col-lg-6 ml-lg-auto col-xl-7 mt-lg-n5">
                            <div class="table-responsive">
                                <table class="table table-borderless table-sm">
                                    <tbody>
                                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['steps']->value, 'step');
$_smarty_tpl->tpl_vars['step']->index = -1;
$_smarty_tpl->tpl_vars['step']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['step']->value) {
$_smarty_tpl->tpl_vars['step']->do_else = false;
$_smarty_tpl->tpl_vars['step']->index++;
$__foreach_step_3_saved = $_smarty_tpl->tpl_vars['step'];
?>
                                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['step']->value->getQuestions(), 'question');
$_smarty_tpl->tpl_vars['question']->index = -1;
$_smarty_tpl->tpl_vars['question']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['question']->value) {
$_smarty_tpl->tpl_vars['question']->do_else = false;
$_smarty_tpl->tpl_vars['question']->index++;
$_smarty_tpl->tpl_vars['question']->first = !$_smarty_tpl->tpl_vars['question']->index;
$__foreach_question_4_saved = $_smarty_tpl->tpl_vars['question'];
?>
                                            <tr>
                                                <?php if ($_smarty_tpl->tpl_vars['question']->first) {?>
                                                    <td>
                                                        <a href="#" class="btn btn-link btn-sm mt-n1 text-primary" data-setup-step="<?php echo $_smarty_tpl->tpl_vars['step']->value->getID();?>
">
                                                            <span class="icon-hover">
                                                                <span class="fal fa-edit"></span>
                                                                <span class="fas fa-edit"></span>
                                                            </span>
                                                        </a>
                                                    </td>
                                                <?php } else { ?>
                                                    <td></td>
                                                <?php }?>
                                                <td>
                                                    <span class="form-title mb-0">
                                                        <?php if ($_smarty_tpl->tpl_vars['question']->value->getSummaryText() !== null) {?>
                                                            <?php echo $_smarty_tpl->tpl_vars['question']->value->getSummaryText();?>

                                                        <?php } else { ?>
                                                            <?php echo $_smarty_tpl->tpl_vars['question']->value->getText();?>

                                                        <?php }?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span data-setup-summary-placeholder="question-<?php echo $_smarty_tpl->tpl_vars['question']->value->getID();?>
">-</span>
                                                </td>
                                            </tr>
                                        <?php
$_smarty_tpl->tpl_vars['question'] = $__foreach_question_4_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                    <?php
$_smarty_tpl->tpl_vars['step'] = $__foreach_step_3_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="setup-slide row" data-setup-slide="<?php echo $_smarty_tpl->tpl_vars['stepID']->value+2;?>
">
                        <div class="col-lg-4 mb-5 mb-lg-0">
                            <span class="setup-subheadline"><?php echo __('thankYouText');?>
</span>
                            <p><?php echo __('thankYouTextDesc');?>
</p>

                            <div class="note small mt-5">
                                <p class="form-title"><?php echo __('installedPlugins');?>
:</p>
                                <p id="installed-plugins">
                                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['steps']->value, 'step');
$_smarty_tpl->tpl_vars['step']->index = -1;
$_smarty_tpl->tpl_vars['step']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['step']->value) {
$_smarty_tpl->tpl_vars['step']->do_else = false;
$_smarty_tpl->tpl_vars['step']->index++;
$__foreach_step_5_saved = $_smarty_tpl->tpl_vars['step'];
?>
                                        <?php if ($_smarty_tpl->tpl_vars['step']->value->getID() === 3 || $_smarty_tpl->tpl_vars['step']->value->getID() === 4) {?>
                                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['step']->value->getQuestions(), 'question');
$_smarty_tpl->tpl_vars['question']->index = -1;
$_smarty_tpl->tpl_vars['question']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['question']->value) {
$_smarty_tpl->tpl_vars['question']->do_else = false;
$_smarty_tpl->tpl_vars['question']->index++;
$_smarty_tpl->tpl_vars['question']->first = !$_smarty_tpl->tpl_vars['question']->index;
$__foreach_question_6_saved = $_smarty_tpl->tpl_vars['question'];
?>
                                                <span data-setup-summary-placeholder="question-<?php echo $_smarty_tpl->tpl_vars['question']->value->getID();?>
"></span>
                                            <?php
$_smarty_tpl->tpl_vars['question'] = $__foreach_question_6_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                        <?php }?>
                                    <?php
$_smarty_tpl->tpl_vars['step'] = $__foreach_step_5_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                </p>
                                <p>
                                    <span class="fal fa-exclamation-triangle text-warning mr-2"></span>
                                    <?php echo __('thankYouTextPluginsInstalled');?>

                                </p>
                            </div>
                        </div>
                        <div class="col-lg-6 ml-lg-auto col-xl-7 mt-lg-n5">
                            <img class="img-fluid img-setup-guide d-none d-lg-block" src="<?php echo $_smarty_tpl->tpl_vars['templateBaseURL']->value;?>
img/setup-assistant-guide.svg" width="188" height="135" alt="Guide">
                            <p class="mt-n3"><?php echo __('finalizeHelpNotice');?>
</p>

                            <div class="form-row">
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationGuideLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationGuide');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationGuideDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationSupportLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationSupport');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationSupportDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationExtensionStoreLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationExtensionStore');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationExtensionStoreDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationIssuetrackerLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationIssuetracker');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationIssuetrackerDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationForumLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationForum');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationForumDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationReleaseForumLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationReleaseForum');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationReleaseForumDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationBlogLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationBlog');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationBlogDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6 mb-2">
                                    <a href="<?php echo __('recommendationJTLSearchLink');?>
" class="card setup-card h-100" target="_blank">
                                        <div class="card-body">
                                            <span class="setup-subheadline mt-0 mb-2"><?php echo __('recommendationJTLSearch');?>
</span>
                                            <p class="text-muted small m-0"><?php echo __('recommendationJTLSearchDesc');?>
</p>
                                            <span class="icon-hover text-primary icon-more">
                                                <span class="fal fa-long-arrow-right"></span>
                                                <span class="fas fa-long-arrow-right"></span>
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-lg-6">
                                    <ul class="list-unstyled d-flex flex-row flex-wrap mb-0">
                                        <li>
                                            <?php echo __('followUs');?>

                                        </li>
                                        <li class="ml-1">
                                            <a href="//www.facebook.com/JTLSoftware" target="_blank">
                                                <i class="fab fa-facebook-f"></i>
                                            </a>
                                        </li>
                                        <li class="ml-2">
                                            <a href="//www.twitter.com/jtlsoftware" target="_blank">
                                                <i class="fab fa-twitter"></i>
                                            </a>
                                        </li>
                                        <li class="ml-2">
                                            <a href="//www.youtube.com/user/JTLSoftwareGmbH" target="_blank">
                                                <i class="fab fa-youtube"></i>
                                            </a>
                                        </li>
                                        <li class="ml-2">
                                            <a href="//www.xing.com/companies/jtl-softwaregmbh" target="_blank">
                                                <i class="fab fa-xing"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col">
                            <button type="button" class="btn btn-outline-primary min-w-sm my-2 my-sm-0 w-100 w-sm-auto" data-setup-prev><?php echo __('back');?>
</button>
                        </div>
                        <div class="col text-right">
                            <button type="button" class="btn btn-primary min-w-sm ml-sm-3 my-2 my-sm-0 w-100 w-sm-auto" data-setup-next><?php echo __('next');?>
</button>
                            <button type="submit" class="btn btn-primary min-w-sm ml-sm-3 my-2 my-sm-0 w-100 w-sm-auto" data-setup-submit><?php echo __('confirm');?>
</button>
                            <a href="index.php" class="btn btn-primary min-w-sm ml-sm-3 my-2 my-sm-0 w-100 w-sm-auto" data-setup-close><?php echo __('finalize');?>
</a>
                            <a href="wizard.php?action=auth&wizard-authenticated=<?php echo count($_smarty_tpl->tpl_vars['steps']->value)+1;?>
" class="btn btn-primary min-w-sm ml-sm-3 my-2 my-sm-0 w-100 w-sm-auto d-none" data-setup-auth><?php echo __('authButton');?>
</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $_smarty_tpl->_subTemplateRender('file:tpl_inc/footer.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
