<?php

use JTL\Alert\Alert;
use JTL\Backend\Notification;
use JTL\Backend\NotificationEntry;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Shop;
use JTL\Shopsetting;

require_once __DIR__ . '/includes/admininclude.php';
require_once __DIR__ . '/includes/einstellungen_inc.php';
require_once PFAD_ROOT . PFAD_INCLUDES . 'suche_inc.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('SETTINGS_ARTICLEOVERVIEW_VIEW', true, true);
$sectionID        = CONF_ARTIKELUEBERSICHT;
$conf             = Shop::getSettings([$sectionID]);
$db               = Shop::Container()->getDB();
$standardwaehrung = $db->select('twaehrung', 'cStandard', 'Y');
$mysqlVersion     = $db->getSingleObject("SHOW VARIABLES LIKE 'innodb_version'")->Value;
$step             = 'einstellungen bearbeiten';
$Conf             = [];
$createIndex      = false;
$alertHelper      = Shop::Container()->getAlertService();

Shop::Container()->getGetText()->loadAdminLocale('pages/einstellungen');

if (Request::getVar('action') === 'createIndex') {
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Content-type: application/json');

    $index = mb_convert_case(Text::xssClean($_GET['index']), MB_CASE_LOWER);

    if (!in_array($index, ['tartikel', 'tartikelsprache'], true)) {
        header(Request::makeHTTPHeader(403));
        echo json_encode((object)['error' => __('errorIndexInvalid')]);
        exit;
    }

    try {
        if ($db->getSingleObject("SHOW INDEX FROM $index WHERE KEY_NAME = 'idx_{$index}_fulltext'")) {
            $db->query("ALTER TABLE $index DROP KEY idx_{$index}_fulltext");
        }
    } catch (Exception $e) {
        // Fehler beim Index löschen ignorieren
    }

    if (Request::getVar('create') === 'Y') {
        $searchCols = array_map(static function ($item) {
            return explode('.', $item, 2)[1];
        }, JTL\Filter\States\BaseSearchQuery::getSearchRows());

        switch ($index) {
            case 'tartikel':
                $rows = array_intersect(
                    $searchCols,
                    ['cName', 'cSeo', 'cSuchbegriffe',
                     'cArtNr', 'cKurzBeschreibung',
                     'cBeschreibung', 'cBarcode',
                     'cISBN', 'cHAN', 'cAnmerkung'
                    ]
                );
                break;
            case 'tartikelsprache':
                $rows = array_intersect(
                    $searchCols,
                    ['cName', 'cSeo', 'cKurzBeschreibung', 'cBeschreibung']
                );
                break;
            default:
                header(Request::makeHTTPHeader(403));
                echo json_encode((object)['error' => __('errorIndexInvalid')]);
                exit;
        }

        try {
            $db->query('UPDATE tsuchcache SET dGueltigBis = DATE_ADD(NOW(), INTERVAL 10 MINUTE)');
            $res = $db->getPDOStatement(
                "ALTER TABLE $index
                    ADD FULLTEXT KEY idx_{$index}_fulltext (" . implode(', ', $rows) . ')'
            );
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res === 0) {
            $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorIndexNotCreatable'), 'errorIndexNotCreatable');
            $shopSettings = Shopsetting::getInstance();
            $settings     = $shopSettings[Shopsetting::mapSettingName(CONF_ARTIKELUEBERSICHT)];

            if ($settings['suche_fulltext'] !== 'N') {
                $settings['suche_fulltext'] = 'N';
                saveAdminSectionSettings($sectionID, $settings);

                Shop::Container()->getCache()->flushTags([
                    CACHING_GROUP_OPTION,
                    CACHING_GROUP_CORE,
                    CACHING_GROUP_ARTICLE,
                    CACHING_GROUP_CATEGORY
                ]);
                $shopSettings->reset();
            }
        } else {
            $alertHelper->addAlert(
                Alert::TYPE_SUCCESS,
                __('successIndexCreate'),
                'successIndexCreate',
                ['saveInSession' => true]
            );
        }
    } else {
        $alertHelper->addAlert(
            Alert::TYPE_SUCCESS,
            __('successIndexDelete'),
            'successIndexDelete',
            ['saveInSession' => true]
        );
    }

    header(Request::makeHTTPHeader(200));
    exit;
}

if (Request::postInt('einstellungen_bearbeiten') === 1 && Form::validateToken()) {
    $sucheFulltext = in_array(Request::postVar('suche_fulltext', []), ['Y', 'B'], true);
    if ($sucheFulltext) {
        if (version_compare($mysqlVersion, '5.6', '<')) {
            //Volltextindizes werden von MySQL mit InnoDB erst ab Version 5.6 unterstützt
            $_POST['suche_fulltext'] = 'N';
            $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorFulltextSearchMYSQL'), 'errorFulltextSearchMYSQL');
        } else {
            // Bei Volltextsuche die Mindeswortlänge an den DB-Parameter anpassen
            $currentVal                 = $db->getSingleObject('SELECT @@ft_min_word_len AS ft_min_word_len');
            $_POST['suche_min_zeichen'] = $currentVal->ft_min_word_len ?? $_POST['suche_min_zeichen'];
        }
    }

    $shopSettings = Shopsetting::getInstance();
    $alertHelper->addAlert(
        Alert::TYPE_SUCCESS,
        saveAdminSectionSettings($sectionID, $_POST),
        'saveSettings'
    );

    Shop::Container()->getCache()->flushTags(
        [CACHING_GROUP_OPTION, CACHING_GROUP_CORE, CACHING_GROUP_ARTICLE, CACHING_GROUP_CATEGORY]
    );
    $shopSettings->reset();

    $fulltextChanged = false;
    foreach ([
            'suche_fulltext',
            'suche_prio_name',
            'suche_prio_suchbegriffe',
            'suche_prio_artikelnummer',
            'suche_prio_kurzbeschreibung',
            'suche_prio_beschreibung',
            'suche_prio_ean',
            'suche_prio_isbn',
            'suche_prio_han',
            'suche_prio_anmerkung'
        ] as $sucheParam) {
        if (isset($_POST[$sucheParam]) && ($_POST[$sucheParam] != $conf['artikeluebersicht'][$sucheParam])) {
            $fulltextChanged = true;
            break;
        }
    }
    if ($fulltextChanged) {
        $createIndex = $sucheFulltext ? 'Y' : 'N';
    }

    if ($sucheFulltext && $fulltextChanged) {
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successSearchActivate'), 'successSearchActivate');
    } elseif ($fulltextChanged) {
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successSearchDeactivate'), 'successSearchDeactivate');
    }

    $conf = Shop::getSettings([$sectionID]);
}

$section = $db->select('teinstellungensektion', 'kEinstellungenSektion', $sectionID);
if ($conf['artikeluebersicht']['suche_fulltext'] !== 'N'
    && (!$db->getSingleObject("SHOW INDEX FROM tartikel WHERE KEY_NAME = 'idx_tartikel_fulltext'")
    || !$db->getSingleObject("SHOW INDEX FROM tartikelsprache WHERE KEY_NAME = 'idx_tartikelsprache_fulltext'"))) {
    $alertHelper->addAlert(
        Alert::TYPE_ERROR,
        __('errorCreateTime') .
        '<a href="sucheinstellungen.php" title="Aktualisieren"><i class="alert-danger fa fa-refresh"></i></a>',
        'errorCreateTime'
    );
    Notification::getInstance($db)->add(
        NotificationEntry::TYPE_WARNING,
        __('indexCreate'),
        'sucheinstellungen.php'
    );
}

$smarty->assign('action', 'sucheinstellungen.php')
       ->assign('kEinstellungenSektion', $sectionID)
       ->assign('Sektion', $section)
       ->assign('Conf', getAdminSectionSettings(CONF_ARTIKELUEBERSICHT))
       ->assign('cPrefDesc', filteredConfDescription($sectionID))
       ->assign('cPrefURL', $smarty->getConfigVars('prefURL' . $sectionID))
       ->assign('step', $step)
       ->assign('supportFulltext', version_compare($mysqlVersion, '5.6', '>='))
       ->assign('createIndex', $createIndex)
       ->assign('waehrung', $standardwaehrung->cName)
       ->display('sucheinstellungen.tpl');
