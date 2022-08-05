<?php

use JTL\Alert\Alert;
use JTL\Customer\Customer;
use JTL\Customer\CustomerGroup;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Newsletter\Admin;
use JTL\Newsletter\Newsletter;
use JTL\Pagination\Pagination;
use JTL\Session\Frontend;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('MODULE_NEWSLETTER_VIEW', true, true);

$db            = Shop::Container()->getDB();
$conf          = Shop::getSettings([CONF_NEWSLETTER]);
$alertHelper   = Shop::Container()->getAlertService();
$newsletterTPL = null;
$step          = 'uebersicht';
$option        = '';
$admin         = new Admin($db, $alertHelper);

$inactiveSearchSQL         = new stdClass();
$inactiveSearchSQL->cJOIN  = '';
$inactiveSearchSQL->cWHERE = '';
$activeSearchSQL           = new stdClass();
$activeSearchSQL->cJOIN    = '';
$activeSearchSQL->cWHERE   = '';
$customerGroup             = $db->select('tkundengruppe', 'cStandard', 'Y');
$_SESSION['Kundengruppe']  = new CustomerGroup((int)$customerGroup->kKundengruppe);
setzeSprache();
$languageID = (int)$_SESSION['editLanguageID'];
$instance   = new Newsletter($db, $conf);
$postData   = Text::filterXSS($_POST);
if (Form::validateToken()) {
    if (Request::postInt('einstellungen') === 1) {
        if (isset($postData['speichern']) || Request::postVar('resetSetting') !== null) {
            $step = 'uebersicht';
            $alertHelper->addAlert(
                Alert::TYPE_SUCCESS,
                saveAdminSectionSettings(CONF_NEWSLETTER, $_POST),
                'saveSettings'
            );
            $admin->setNewsletterCheckboxStatus();
        }
    } elseif (Request::postInt('newsletterabonnent_loeschen') === 1
        || (Request::verifyGPCDataInt('inaktiveabonnenten') === 1 && isset($postData['abonnentloeschenSubmit']))
    ) {
        if ($admin->deleteSubscribers($postData['kNewsletterEmpfaenger'] ?? [])) {
            $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successNewsletterAboDelete'), 'successNewsletterAboDelete');
        } else {
            $alertHelper->addAlert(
                Alert::TYPE_ERROR,
                __('errorAtLeastOneNewsletterAbo'),
                'errorAtLeastOneNewsletterAbo'
            );
        }
    } elseif (isset($postData['abonnentfreischaltenSubmit']) && Request::verifyGPCDataInt('inaktiveabonnenten') === 1) {
        if ($admin->activateSubscribers($postData['kNewsletterEmpfaenger'])) {
            $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successNewsletterAbounlock'), 'successNewsletterAbounlock');
        } else {
            $alertHelper->addAlert(
                Alert::TYPE_ERROR,
                __('errorAtLeastOneNewsletterAbo'),
                'errorAtLeastOneNewsletterAbo'
            );
        }
    } elseif (Request::postInt('newsletterabonnent_neu') === 1) {
        $newsletter = $admin->addRecipient($instance, $postData);
        $smarty->assign('oNewsletter', $newsletter);
    } elseif (Request::postInt('newsletterqueue') === 1) { // Queue
        if (isset($postData['loeschen'])) {
            if (!empty($postData['kNewsletterQueue']) && is_array($postData['kNewsletterQueue'])) {
                $admin->deleteQueue($postData['kNewsletterQueue']);
            } else {
                $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorAtLeastOneNewsletter'), 'errorAtLeastOneNewsletter');
            }
        }
    } elseif (Request::postInt('newsletterhistory') === 1 || Request::getInt('newsletterhistory') === 1) {
        if (isset($postData['loeschen'])) {
            if (is_array($postData['kNewsletterHistory'])) {
                $admin->deleteHistory($postData['kNewsletterHistory']);
            } else {
                $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorAtLeastOneHistory'), 'errorAtLeastOneHistory');
            }
        } elseif (isset($_GET['anzeigen'])) {
            $step      = 'history_anzeigen';
            $historyID = (int)$_GET['anzeigen'];
            $hist      = $db->getSingleObject(
                "SELECT kNewsletterHistory, cBetreff, cHTMLStatic, cKundengruppe,
                    DATE_FORMAT(dStart, '%d.%m.%Y %H:%i') AS Datum
                    FROM tnewsletterhistory
                    WHERE kNewsletterHistory = :hid
                        AND kSprache = :lid",
                ['hid' => $historyID, 'lid' => $languageID]
            );
            if ($hist !== null && $hist->kNewsletterHistory > 0) {
                $smarty->assign('oNewsletterHistory', $hist);
            }
        }
    } elseif (mb_strlen(Request::verifyGPDataString('cSucheInaktiv')) > 0) { // Inaktive Abonnentensuche
        $query = $db->escape(Text::filterXSS(Request::verifyGPDataString('cSucheInaktiv')));
        if (mb_strlen($query) > 0) {
            $inactiveSearchSQL->cWHERE = " AND (tnewsletterempfaenger.cVorname LIKE '%" . $query .
                "%' OR tnewsletterempfaenger.cNachname LIKE '%" . $query .
                "%' OR tnewsletterempfaenger.cEmail LIKE '%" . $query . "%')";
        }

        $smarty->assign('cSucheInaktiv', $query);
    } elseif (mb_strlen(Request::verifyGPDataString('cSucheAktiv')) > 0) { // Aktive Abonnentensuche
        $query = $db->escape(Text::filterXSS(Request::verifyGPDataString('cSucheAktiv')));
        if (mb_strlen($query) > 0) {
            $activeSearchSQL->cWHERE = " AND (tnewsletterempfaenger.cVorname LIKE '%" . $query .
                "%' OR tnewsletterempfaenger.cNachname LIKE '%" . $query .
                "%' OR tnewsletterempfaenger.cEmail LIKE '%" . $query . "%')";
        }

        $smarty->assign('cSucheAktiv', $query);
    } elseif (Request::verifyGPCDataInt('vorschau') > 0) { // Vorschau
        $nlTemplateID = Request::verifyGPCDataInt('vorschau');
        // Infos der Vorlage aus DB holen
        $newsletterTPL = $db->getSingleObject(
            "SELECT *, DATE_FORMAT(dStartZeit, '%d.%m.%Y %H:%i') AS Datum
                FROM tnewslettervorlage
                WHERE kNewsletterVorlage = :tid",
            ['tid' => $nlTemplateID]
        );
        $preview       = null;
        if (Request::verifyGPCDataInt('iframe') === 1) {
            $step = 'vorlage_vorschau_iframe';
            $smarty->assign(
                'cURL',
                'newsletter.php?vorschau=' . $nlTemplateID . '&token=' . $_SESSION['jtl_token']
            );
            $preview = $instance->getPreview($newsletterTPL);
        } elseif (isset($newsletterTPL->kNewsletterVorlage) && $newsletterTPL->kNewsletterVorlage > 0) {
            $step                 = 'vorlage_vorschau';
            $newsletterTPL->oZeit = $admin->getDateData($newsletterTPL->dStartZeit);
            $preview              = $instance->getPreview($newsletterTPL);
        }
        if (is_string($preview)) {
            $alertHelper->addAlert(Alert::TYPE_ERROR, $preview, 'errorNewsletterPreview');
        }
        $smarty->assign('oNewsletterVorlage', $newsletterTPL)
            ->assign('NettoPreise', Frontend::getCustomerGroup()->getIsMerchant());
    } elseif (Request::verifyGPCDataInt('newslettervorlagenstd') === 1) { // Vorlagen Std
        $productNos       = $postData['cArtNr'] ?? null;
        $customerGroupIDs = $postData['kKundengruppe'] ?? null;
        $groupString      = '';
        // Kundengruppen in einen String bauen
        if (is_array($customerGroupIDs) && count($customerGroupIDs) > 0) {
            foreach ($customerGroupIDs as $customerGroupID) {
                $groupString .= ';' . $customerGroupID . ';';
            }
        }
        $smarty->assign('oKampagne_arr', holeAlleKampagnen(false, true))
            ->assign('cTime', time());
        // Vorlage speichern
        if (Request::verifyGPCDataInt('vorlage_std_speichern') === 1) {
            $step = $admin->save(Request::verifyGPCDataInt('kNewslettervorlageStd'), $smarty);
        } elseif (Request::verifyGPCDataInt('editieren') > 0) { // Editieren
            $step = $admin->edit(Request::verifyGPCDataInt('editieren'), $smarty);
        }
        // Vorlage Std erstellen
        if (Request::verifyGPCDataInt('vorlage_std_erstellen') === 1
            && Request::verifyGPCDataInt('kNewsletterVorlageStd') > 0
        ) {
            $step                  = 'vorlage_std_erstellen';
            $kNewsletterVorlageStd = Request::verifyGPCDataInt('kNewsletterVorlageStd');
            // Hole Std Vorlage
            $tpl = $admin->getDefaultTemplate($kNewsletterVorlageStd);
            $smarty->assign('oNewslettervorlageStd', $tpl);
        }
    } elseif (Request::verifyGPCDataInt('newslettervorlagen') === 1) {
        // Vorlagen
        $smarty->assign('oKampagne_arr', holeAlleKampagnen(false, true));
        $productNos       = $postData['cArtNr'] ?? null;
        $customerGroupIDs = $postData['kKundengruppe'] ?? [];
        $groupString      = '';
        // Kundengruppen in einen String bauen
        if (is_array($customerGroupIDs) && count($customerGroupIDs) > 0) {
            foreach ($customerGroupIDs as $customerGroupID) {
                $groupString .= ';' . (int)$customerGroupID . ';';
            }
        }
        // Vorlage hinzufuegen
        if (isset($postData['vorlage_erstellen'])) {
            $step   = 'vorlage_erstellen';
            $option = 'erstellen';
        } elseif (Request::getInt('editieren') > 0 || Request::getInt('vorbereiten') > 0) {
            // Vorlage editieren/vorbereiten
            $step         = 'vorlage_erstellen';
            $nlTemplateID = Request::verifyGPCDataInt('vorbereiten');
            if ($nlTemplateID === 0) {
                $nlTemplateID = Request::verifyGPCDataInt('editieren');
            }
            // Infos der Vorlage aus DB holen
            $newsletterTPL = $db->getSingleObject(
                "SELECT *, DATE_FORMAT(dStartZeit, '%d.%m.%Y %H:%i') AS Datum
                    FROM tnewslettervorlage
                    WHERE kNewsletterVorlage = :tid",
                ['tid' => $nlTemplateID]
            );
            if ($newsletterTPL !== null && $newsletterTPL->kNewsletterVorlage > 0) {
                $newsletterTPL->oZeit       = $admin->getDateData($newsletterTPL->dStartZeit);
                $productData                = $admin->getProductData($newsletterTPL->cArtikel);
                $newsletterTPL->cArtikel    = mb_substr(
                    mb_substr($newsletterTPL->cArtikel, 1),
                    0,
                    -1
                );
                $newsletterTPL->cHersteller = mb_substr(
                    mb_substr($newsletterTPL->cHersteller, 1),
                    0,
                    -1
                );
                $newsletterTPL->cKategorie  = mb_substr(
                    mb_substr($newsletterTPL->cKategorie, 1),
                    0,
                    -1
                );
                $smarty->assign('kArtikel_arr', $productData->kArtikel_arr)
                    ->assign('cArtNr_arr', $productData->cArtNr_arr)
                    ->assign('kKundengruppe_arr', $admin->getCustomerGroupData($newsletterTPL->cKundengruppe));
            }

            $smarty->assign('oNewsletterVorlage', $newsletterTPL);
            if (isset($_GET['editieren'])) {
                $option = 'editieren';
            }
        } elseif (isset($postData['speichern'])) { // Vorlage speichern
            $checks = $admin->saveTemplate($_POST);
            if (is_array($checks) && count($checks) > 0) {
                $step = 'vorlage_erstellen';
                $smarty->assign('cPlausiValue_arr', $checks)
                    ->assign('cPostVar_arr', $_POST)
                    ->assign('oNewsletterVorlage', $newsletterTPL);
            }
        } elseif (isset($postData['speichern_und_senden'])) { // Vorlage speichern und senden
            unset($newsletter, $oKunde, $mailRecipient);
            $res = $admin->saveAndContinue($newsletterTPL, $smarty);
            if ($res === false) {
                $step = 'vorlage_erstellen';
            }
        } elseif (isset($postData['speichern_und_testen'])) { // Vorlage speichern und testen
            $res = $admin->saveAndTest($newsletterTPL, $smarty);
            if ($res === false) {
                $step = 'vorlage_erstellen';
            }
        } elseif (isset($postData['loeschen'])) { // Vorlage loeschen
            $step = 'uebersicht';
            $admin->deleteTemplates($postData['kNewsletterVorlage'] ?? []);
        }
        $smarty->assign('cOption', $option);
    }
}
if ($step === 'uebersicht') {
    $recipientsCount   = (int)$db->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tnewsletterempfaenger
            WHERE tnewsletterempfaenger.nAktiv = 0' . $inactiveSearchSQL->cWHERE
    )->cnt;
    $queueCount        = (int)$db->getSingleObject(
        "SELECT COUNT(*) AS cnt
            FROM tjobqueue
            WHERE jobType = 'newsletter'"
    )->cnt;
    $templateCount     = (int)$db->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tnewslettervorlage
            WHERE kSprache = :lid',
        ['lid' => $languageID],
    )->cnt;
    $historyCount      = (int)$db->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tnewsletterhistory
            WHERE kSprache = :lid',
        ['lid' => $languageID]
    )->cnt;
    $pagiInactive      = (new Pagination('inaktive'))
        ->setItemCount($recipientsCount)
        ->assemble();
    $pagiQueue         = (new Pagination('warteschlange'))
        ->setItemCount($queueCount)
        ->assemble();
    $pagiTemplates     = (new Pagination('vorlagen'))
        ->setItemCount($templateCount)
        ->assemble();
    $pagiHistory       = (new Pagination('history'))
        ->setItemCount($historyCount)
        ->assemble();
    $pagiSubscriptions = (new Pagination('alle'))
        ->setItemCount($admin->getSubscriberCount($activeSearchSQL))
        ->assemble();
    $queue             = $db->getObjects(
        "SELECT l.cBetreff, q.tasksExecuted, c.cronID, c.foreignKeyID, c.startDate as 'Datum'
            FROM tcron c
                LEFT JOIN tjobqueue q ON c.cronID = q.cronID
                LEFT JOIN tnewsletter l ON c.foreignKeyID = l.kNewsletter
            WHERE c.jobType = 'newsletter'
                AND l.kSprache = :langID
            ORDER BY c.startDate DESC
            LIMIT " . $pagiQueue->getLimitSQL(),
        ['langID' => $languageID]
    );
    if (!($instance instanceof Newsletter)) {
        $instance = new Newsletter($db, $conf);
    }
    foreach ($queue as $entry) {
        $entry->kNewsletter       = (int)$entry->foreignKeyID;
        $entry->nLimitN           = (int)$entry->tasksExecuted;
        $entry->kNewsletterQueue  = (int)$entry->cronID;
        $recipient                = $instance->getRecipients($entry->kNewsletter);
        $entry->nAnzahlEmpfaenger = $recipient->nAnzahl;
        $entry->cKundengruppe_arr = $recipient->cKundengruppe_arr;
    }
    $templates = $db->getObjects(
        'SELECT kNewsletterVorlage, kNewslettervorlageStd, cBetreff, cName
            FROM tnewslettervorlage
            WHERE kSprache = :lid
            ORDER BY kNewsletterVorlage DESC LIMIT ' . $pagiTemplates->getLimitSQL(),
        ['lid' => $languageID]
    );
    foreach ($templates as $template) {
        $template->cBetreff = Text::filterXSS($template->cBetreff);
        $template->cName    = Text::filterXSS($template->cName);
    }
    $defaultData = $db->getObjects(
        'SELECT *
            FROM tnewslettervorlagestd
            WHERE kSprache = :lid
            ORDER BY cName',
        ['lid' => $languageID]
    );
    foreach ($defaultData as $tpl) {
        $tpl->oNewsletttervorlageStdVar_arr = $db->getObjects(
            'SELECT *
                FROM tnewslettervorlagestdvar
                WHERE kNewslettervorlageStd = :tid',
            ['tid' => (int)$tpl->kNewslettervorlageStd]
        );
    }
    $inactiveRecipients = $db->getObjects(
        "SELECT tnewsletterempfaenger.kNewsletterEmpfaenger, tnewsletterempfaenger.cVorname AS newsVorname,
            tnewsletterempfaenger.cNachname AS newsNachname, tkunde.cVorname, tkunde.cNachname,
            tnewsletterempfaenger.cEmail, tnewsletterempfaenger.nAktiv, tkunde.kKundengruppe, tkundengruppe.cName,
            DATE_FORMAT(tnewsletterempfaenger.dEingetragen, '%d.%m.%Y %H:%i') AS Datum
            FROM tnewsletterempfaenger
            LEFT JOIN tkunde
                ON tkunde.kKunde = tnewsletterempfaenger.kKunde
            LEFT JOIN tkundengruppe
                ON tkundengruppe.kKundengruppe = tkunde.kKundengruppe
            WHERE tnewsletterempfaenger.nAktiv = 0
            " . $inactiveSearchSQL->cWHERE . '
            ORDER BY tnewsletterempfaenger.dEingetragen DESC
            LIMIT ' . $pagiInactive->getLimitSQL()
    );
    foreach ($inactiveRecipients as $recipient) {
        $customer                = new Customer(isset($recipient->kKunde) ? (int)$recipient->kKunde : null);
        $recipient->cNachname    = Text::filterXSS($customer->cNachname);
        $recipient->newsVorname  = Text::filterXSS($recipient->newsVorname);
        $recipient->newsNachname = Text::filterXSS($recipient->newsNachname);
        $recipient->cVorname     = Text::filterXSS($recipient->cVorname);
        $recipient->cNachname    = Text::filterXSS($recipient->cNachname);
        $recipient->cEmail       = Text::filterXSS($recipient->cEmail);
        $recipient->nAktiv       = (int)$recipient->nAktiv;
    }

    $history              = $db->getObjects(
        "SELECT kNewsletterHistory, nAnzahl, cBetreff, cKundengruppe, DATE_FORMAT(dStart, '%d.%m.%Y %H:%i') AS Datum
            FROM tnewsletterhistory
            WHERE kSprache = :lid
            ORDER BY dStart DESC
            LIMIT " . $pagiHistory->getLimitSQL(),
        ['lid' => $languageID]
    );
    $customerGroupsByName = $db->getObjects(
        'SELECT *
            FROM tkundengruppe
            ORDER BY cName'
    );
    $smarty->assign('kundengruppen', $customerGroupsByName)
        ->assign('oNewsletterQueue_arr', $queue)
        ->assign('oNewsletterVorlage_arr', $templates)
        ->assign('oNewslettervorlageStd_arr', $defaultData)
        ->assign('oNewsletterEmpfaenger_arr', $inactiveRecipients)
        ->assign('oNewsletterHistory_arr', $history)
        ->assign('oConfig_arr', getAdminSectionSettings(CONF_NEWSLETTER))
        ->assign('oAbonnenten_arr', $admin->getSubscribers(
            ' LIMIT ' . $pagiSubscriptions->getLimitSQL(),
            $activeSearchSQL
        ))
        ->assign('nMaxAnzahlAbonnenten', $admin->getSubscriberCount($activeSearchSQL))
        ->assign('oPagiInaktiveAbos', $pagiInactive)
        ->assign('oPagiWarteschlange', $pagiQueue)
        ->assign('oPagiVorlagen', $pagiTemplates)
        ->assign('oPagiHistory', $pagiHistory)
        ->assign('oPagiAlleAbos', $pagiSubscriptions);
}
if (isset($checks) && is_array($checks) && count($checks) > 0) {
    $alertHelper->addAlert(
        Alert::TYPE_ERROR,
        __('errorFillRequired'),
        'plausiErrorFillRequired'
    );
}
$smarty->assign('step', $step)
    ->assign('customerGroups', CustomerGroup::getGroups())
    ->assign('nRand', time())
    ->display('newsletter.tpl');
