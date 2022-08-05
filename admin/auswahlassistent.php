<?php

use JTL\Alert\Alert;
use JTL\Extensions\SelectionWizard\Group;
use JTL\Extensions\SelectionWizard\Question;
use JTL\Extensions\SelectionWizard\Wizard;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Nice;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Smarty\JTLSmarty $smarty */
/** @global \JTL\Backend\AdminAccount $oAccount */
$oAccount->permission('EXTENSION_SELECTIONWIZARD_VIEW', true, true);
$step        = '';
$nice        = Nice::getInstance();
$tab         = 'uebersicht';
$alertHelper = Shop::Container()->getAlertService();
$postData    = Text::filterXSS($_POST);

Shop::Container()->getGetText()->loadConfigLocales();
setzeSprache();
$languageID = (int)$_SESSION['editLanguageID'];
if ($nice->checkErweiterung(SHOP_ERWEITERUNG_AUSWAHLASSISTENT)) {
    $group    = new Group();
    $question = new Question();
    $step     = 'uebersicht';
    $csrfOK   = Form::validateToken();
    if (mb_strlen(Request::verifyGPDataString('tab')) > 0) {
        $tab = Request::verifyGPDataString('tab');
    }
    if (isset($postData['a']) && $csrfOK) {
        if ($postData['a'] === 'newGrp') {
            $step = 'edit-group';
        } elseif ($postData['a'] === 'newQuest') {
            $step = 'edit-question';
        } elseif ($postData['a'] === 'addQuest') {
            $question->cFrage                  = htmlspecialchars(
                $postData['cFrage'],
                ENT_COMPAT | ENT_HTML401,
                JTL_CHARSET
            );
            $question->kMerkmal                = Request::postInt('kMerkmal');
            $question->kAuswahlAssistentGruppe = Request::postInt('kAuswahlAssistentGruppe');
            $question->nSort                   = Request::postInt('nSort');
            $question->nAktiv                  = Request::postInt('nAktiv');

            $checks = [];
            if (Request::postInt('kAuswahlAssistentFrage') > 0) {
                $question->kAuswahlAssistentFrage = Request::postInt('kAuswahlAssistentFrage');
                $checks                           = $question->updateQuestion();
            } else {
                $checks = $question->saveQuestion();
            }

            if ((!is_array($checks) && $checks) || count($checks) === 0) {
                $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successQuestionSaved'), 'successQuestionSaved');
                $tab = 'uebersicht';
            } elseif (is_array($checks) && count($checks) > 0) {
                $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorFillRequired'), 'errorFillRequired');
                $smarty->assign('cPost_arr', $postData)
                    ->assign('cPlausi_arr', $checks)
                    ->assign('kAuswahlAssistentFrage', (int)($postData['kAuswahlAssistentFrage'] ?? 0));
            }
        }
    } elseif ($csrfOK && Request::getVar('a') === 'delQuest' && Request::getInt('q') > 0) {
        if ($question->deleteQuestion([Request::getInt('q')])) {
            $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successQuestionDeleted'), 'successQuestionDeleted');
        } else {
            $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorQuestionDeleted'), 'errorQuestionDeleted');
        }
    } elseif ($csrfOK && Request::getVar('a') === 'editQuest' && Request::getInt('q') > 0) {
        $step = 'edit-question';
        $smarty->assign('oFrage', new Question(Request::getInt('q'), false));
    }

    if (isset($postData['a']) && $csrfOK) {
        if ($postData['a'] === 'addGrp') {
            $group->kSprache      = $languageID;
            $group->cName         = htmlspecialchars(
                $postData['cName'],
                ENT_COMPAT | ENT_HTML401,
                JTL_CHARSET
            );
            $group->cBeschreibung = $postData['cBeschreibung'];
            $group->nAktiv        = Request::postInt('nAktiv');

            $checks = [];
            if (Request::postInt('kAuswahlAssistentGruppe') > 0) {
                $group->kAuswahlAssistentGruppe = Request::postInt('kAuswahlAssistentGruppe');
                $checks                         = $group->updateGroup($postData);
            } else {
                $checks = $group->saveGroup($postData);
            }
            if ((!is_array($checks) && $checks) || count($checks) === 0) {
                $step = 'uebersicht';
                $tab  = 'uebersicht';
                $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successGroupSaved'), 'successGroupSaved');
            } elseif (is_array($checks) && count($checks) > 0) {
                $step = 'edit-group';
                $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorFillRequired'), 'errorFillRequired');
                $smarty->assign('cPost_arr', $postData)
                    ->assign('cPlausi_arr', $checks)
                    ->assign('kAuswahlAssistentGruppe', Request::postInt('kAuswahlAssistentGruppe'));
            }
        } elseif ($postData['a'] === 'delGrp') {
            if ($group->deleteGroup($postData['kAuswahlAssistentGruppe_arr'] ?? [])) {
                $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successGroupDeleted'), 'successGroupDeleted');
            } else {
                $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorGroupDeleted'), 'errorGroupDeleted');
            }
        } elseif ($postData['a'] === 'saveSettings') {
            $step = 'uebersicht';
            $alertHelper->addAlert(
                Alert::TYPE_SUCCESS,
                saveAdminSectionSettings(CONF_AUSWAHLASSISTENT, $postData),
                'saveSettings'
            );
        }
    } elseif ($csrfOK && Request::getVar('a') === 'editGrp' && Request::getInt('g') > 0) {
        $step = 'edit-group';
        $smarty->assign('oGruppe', new Group(Request::getInt('g'), false, false, true));
    }
    if ($step === 'uebersicht') {
        $smarty->assign(
            'oAuswahlAssistentGruppe_arr',
            $group->getGroups($languageID, false, false, true)
        );
    } elseif ($step === 'edit-group') {
        $smarty->assign('oLink_arr', Wizard::getLinks());
    } elseif ($step === 'edit-question') {
        $defaultLanguage = Shop::Container()->getDB()->select('tsprache', 'cShopStandard', 'Y');
        $select          = 'tmerkmal.*';
        $join            = '';
        if ((int)$defaultLanguage->kSprache !== $languageID) {
            $select = 'tmerkmalsprache.*';
            $join   = ' JOIN tmerkmalsprache ON tmerkmalsprache.kMerkmal = tmerkmal.kMerkmal
                            AND tmerkmalsprache.kSprache = ' . $languageID;
        }
        $attributes = Shop::Container()->getDB()->getObjects(
            'SELECT ' . $select . '
                FROM tmerkmal
                ' . $join . '
                ORDER BY tmerkmal.nSort'
        );
        $smarty->assign('oMerkmal_arr', $attributes)
            ->assign(
                'oAuswahlAssistentGruppe_arr',
                $group->getGroups($languageID, false, false, true)
            );
    }
} else {
    $smarty->assign('noModule', true);
}
$smarty->assign('step', $step)
    ->assign('cTab', $tab)
    ->assign('languageID', $languageID)
    ->assign('oConfig_arr', getAdminSectionSettings(CONF_AUSWAHLASSISTENT))
    ->display('auswahlassistent.tpl');
