<?php

use JTL\Alert\Alert;
use JTL\CheckBox;
use JTL\Customer\CustomerGroup;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Language\LanguageHelper;
use JTL\Pagination\Pagination;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('CHECKBOXES_VIEW', true, true);

require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'checkbox_inc.php';
$alertHelper = Shop::Container()->getAlertService();
$step        = 'uebersicht';
$checkbox    = new CheckBox();
$tab         = $step;
if (mb_strlen(Request::verifyGPDataString('tab')) > 0) {
    $tab = Request::verifyGPDataString('tab');
}
if (isset($_POST['erstellenShowButton'])) {
    $tab = 'erstellen';
} elseif (Request::verifyGPCDataInt('uebersicht') === 1 && Form::validateToken()) {
    $checkboxIDs = Request::verifyGPDataIntegerArray('kCheckBox');
    if (isset($_POST['checkboxAktivierenSubmit'])) {
        $checkbox->activate($checkboxIDs);
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successCheckboxActivate'), 'successCheckboxActivate');
    } elseif (isset($_POST['checkboxDeaktivierenSubmit'])) {
        $checkbox->deactivate($checkboxIDs);
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successCheckboxDeactivate'), 'successCheckboxDeactivate');
    } elseif (isset($_POST['checkboxLoeschenSubmit'])) {
        $checkbox->delete($checkboxIDs);
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successCheckboxDelete'), 'successCheckboxDelete');
    }
} elseif (Request::verifyGPCDataInt('edit') > 0) {
    $checkboxID = Request::verifyGPCDataInt('edit');
    $step       = 'erstellen';
    $tab        = $step;
    $smarty->assign('oCheckBox', new CheckBox($checkboxID));
} elseif (Request::verifyGPCDataInt('erstellen') === 1 && Form::validateToken()) {
    $post       = Text::filterXSS($_POST);
    $step       = 'erstellen';
    $checkboxID = Request::verifyGPCDataInt('kCheckBox');
    $languages  = LanguageHelper::getAllLanguages(0, true);
    $checks     = plausiCheckBox($post, $languages);
    if (count($checks) === 0) {
        $checkbox = speicherCheckBox($post, $languages);
        $step     = 'uebersicht';
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successCheckboxCreate'), 'successCheckboxCreate');
    } else {
        $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorFillRequired'), 'errorFillRequired');
        $smarty->assign('cPost_arr', $post)
            ->assign('cPlausi_arr', $checks);
        if ($checkboxID > 0) {
            $smarty->assign('kCheckBox', $checkboxID);
        }
    }
    $tab = $step;
}

$pagination = (new Pagination())
    ->setItemCount($checkbox->getTotalCount())
    ->assemble();
$smarty->assign('oCheckBox_arr', $checkbox->getAll('LIMIT ' . $pagination->getLimitSQL()))
    ->assign('pagination', $pagination)
    ->assign('cAnzeigeOrt_arr', CheckBox::gibCheckBoxAnzeigeOrte())
    ->assign('CHECKBOX_ORT_REGISTRIERUNG', CHECKBOX_ORT_REGISTRIERUNG)
    ->assign('CHECKBOX_ORT_BESTELLABSCHLUSS', CHECKBOX_ORT_BESTELLABSCHLUSS)
    ->assign('CHECKBOX_ORT_NEWSLETTERANMELDUNG', CHECKBOX_ORT_NEWSLETTERANMELDUNG)
    ->assign('CHECKBOX_ORT_KUNDENDATENEDITIEREN', CHECKBOX_ORT_KUNDENDATENEDITIEREN)
    ->assign('CHECKBOX_ORT_KONTAKT', CHECKBOX_ORT_KONTAKT)
    ->assign('customerGroups', CustomerGroup::getGroups())
    ->assign('oLink_arr', Shop::Container()->getDB()->getObjects(
        'SELECT * 
              FROM tlink 
              ORDER BY cName'
    ))
    ->assign('oCheckBoxFunktion_arr', $checkbox->getCheckBoxFunctions())
    ->assign('step', $step)
    ->assign('cTab', $tab)
    ->display('checkbox.tpl');
