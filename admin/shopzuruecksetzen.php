<?php

use JTL\Alert\Alert;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'news_inc.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('RESET_SHOP_VIEW', true, true);
$alertHelper = Shop::Container()->getAlertService();
$db          = Shop::Container()->getDB();
if (Request::postInt('zuruecksetzen') === 1 && Form::validateToken()) {
    $options = $_POST['cOption_arr'];
    if (is_array($options) && count($options) > 0) {
        foreach ($options as $option) {
            switch ($option) {
                // JTL-Wawi Inhalte
                case 'artikel':
                    $db->query('SET FOREIGN_KEY_CHECKS = 0;');
                    $db->query('TRUNCATE tartikel');
                    $db->query('TRUNCATE tartikelabnahme');
                    $db->query('TRUNCATE tartikelattribut');
                    $db->query('TRUNCATE tartikelkategorierabatt');
                    $db->query('TRUNCATE tartikelkonfiggruppe');
                    $db->query('TRUNCATE tartikelmerkmal');
                    $db->query('TRUNCATE tartikelpict');
                    $db->query('TRUNCATE tartikelsichtbarkeit');
                    $db->query('TRUNCATE tartikelsonderpreis');
                    $db->query('TRUNCATE tartikelsprache');
                    $db->query('TRUNCATE tartikelwarenlager');
                    $db->query('TRUNCATE tattribut');
                    $db->query('TRUNCATE tattributsprache');
                    $db->query('TRUNCATE tbild');
                    $db->query('TRUNCATE teigenschaft');
                    $db->query('TRUNCATE teigenschaftkombiwert');
                    $db->query('TRUNCATE teigenschaftsichtbarkeit');
                    $db->query('TRUNCATE teigenschaftsprache');
                    $db->query('TRUNCATE teigenschaftwert');
                    $db->query('TRUNCATE teigenschaftwertabhaengigkeit');
                    $db->query('TRUNCATE teigenschaftwertaufpreis');
                    $db->query('TRUNCATE teigenschaftwertpict');
                    $db->query('TRUNCATE teigenschaftwertsichtbarkeit');
                    $db->query('TRUNCATE teigenschaftwertsprache');
                    $db->query('TRUNCATE teinheit');
                    $db->query('TRUNCATE tkategorie');
                    $db->query('TRUNCATE tkategorieartikel');
                    $db->query('TRUNCATE tkategorieattribut');
                    $db->query('TRUNCATE tkategorieattributsprache');
                    $db->query('TRUNCATE tkategoriekundengruppe');
                    $db->query('TRUNCATE tkategoriemapping');
                    $db->query('TRUNCATE tkategoriepict');
                    $db->query('TRUNCATE tkategoriesichtbarkeit');
                    $db->query('TRUNCATE tkategoriesprache');
                    $db->query('TRUNCATE tmediendatei');
                    $db->query('TRUNCATE tmediendateiattribut');
                    $db->query('TRUNCATE tmediendateisprache');
                    $db->query('TRUNCATE tmerkmal');
                    $db->query('TRUNCATE tmerkmalsprache');
                    $db->query('TRUNCATE tmerkmalwert');
                    $db->query('TRUNCATE tmerkmalwertbild');
                    $db->query('TRUNCATE tmerkmalwertsprache');
                    $db->query('TRUNCATE tpreis');
                    $db->query('TRUNCATE tpreisdetail');
                    $db->query('TRUNCATE tsonderpreise');
                    $db->query('TRUNCATE txsell');
                    $db->query('TRUNCATE txsellgruppe');
                    $db->query('TRUNCATE thersteller');
                    $db->query('TRUNCATE therstellersprache');
                    $db->query('TRUNCATE tlieferstatus');
                    $db->query('TRUNCATE tkonfiggruppe');
                    $db->query('TRUNCATE tkonfigitem');
                    $db->query('TRUNCATE tkonfiggruppesprache');
                    $db->query('TRUNCATE tkonfigitempreis');
                    $db->query('TRUNCATE tkonfigitemsprache');
                    $db->query('TRUNCATE twarenlager');
                    $db->query('TRUNCATE twarenlagersprache');
                    $db->query('TRUNCATE tuploadschema');
                    $db->query('TRUNCATE tuploadschemasprache');
                    $db->query('TRUNCATE tmasseinheit');
                    $db->query('TRUNCATE tmasseinheitsprache');
                    $db->query('SET FOREIGN_KEY_CHECKS = 1;');

                    $db->query(
                        "DELETE FROM tseo
                            WHERE cKey = 'kArtikel'
                            OR cKey = 'kKategorie'
                            OR cKey = 'kMerkmalWert'
                            OR cKey = 'kHersteller'"
                    );
                    break;

                case 'steuern':
                    $db->query('TRUNCATE tsteuerklasse');
                    $db->query('TRUNCATE tsteuersatz');
                    $db->query('TRUNCATE tsteuerzone');
                    $db->query('TRUNCATE tsteuerzoneland');
                    break;

                case 'revisions':
                    $db->query('TRUNCATE trevisions');
                    break;

                // Shopinhalte
                case 'news':
                    foreach ($db->getObjects('SELECT kNews FROM tnews') as $i) {
                        loescheNewsBilderDir($i->kNews, PFAD_ROOT . PFAD_NEWSBILDER);
                    }
                    $db->query('TRUNCATE tnews');
                    $db->delete('trevisions', 'type', 'news');
                    $db->query('TRUNCATE tnewskategorie');
                    $db->query('TRUNCATE tnewskategorienews');
                    $db->query('TRUNCATE tnewskommentar');
                    $db->query('TRUNCATE tnewsmonatsuebersicht');

                    $db->query(
                        "DELETE FROM tseo
                            WHERE cKey = 'kNews'
                              OR cKey = 'kNewsKategorie'
                              OR cKey = 'kNewsMonatsUebersicht'"
                    );
                    break;

                case 'bestseller':
                    $db->query('TRUNCATE tbestseller');
                    break;

                case 'besucherstatistiken':
                    $db->query('TRUNCATE tbesucher');
                    $db->query('TRUNCATE tbesucherarchiv');
                    $db->query('TRUNCATE tbesuchteseiten');
                    break;

                case 'preisverlaeufe':
                    $db->query('TRUNCATE tpreisverlauf');
                    break;

                case 'verfuegbarkeitsbenachrichtigungen':
                    $db->query(
                        'TRUNCATE tverfuegbarkeitsbenachrichtigung'
                    );
                    break;

                // Benutzergenerierte Inhalte
                case 'suchanfragen':
                    $db->query('TRUNCATE tsuchanfrage');
                    $db->query('TRUNCATE tsuchanfrageerfolglos');
                    $db->query('TRUNCATE tsuchanfragemapping');
                    $db->query('TRUNCATE tsuchanfragencache');
                    $db->query('TRUNCATE tsuchcache');
                    $db->query('TRUNCATE tsuchcachetreffer');

                    $db->delete('tseo', 'cKey', 'kSuchanfrage');
                    break;

                case 'bewertungen':
                    $db->query('TRUNCATE tartikelext');
                    $db->query('TRUNCATE tbewertung');
                    $db->query('TRUNCATE tbewertungguthabenbonus');
                    $db->query('TRUNCATE tbewertunghilfreich');
                    break;

                case 'wishlist':
                    $db->query('TRUNCATE twunschliste');
                    $db->query('TRUNCATE twunschlistepos');
                    $db->query('TRUNCATE twunschlisteposeigenschaft');
                    $db->query('TRUNCATE twunschlisteversand');
                    break;

                case 'comparelist':
                    $db->query('TRUNCATE tvergleichsliste');
                    $db->query('TRUNCATE tvergleichslistepos');
                    break;

                // Shopkunden & Kunden werben Kunden & Bestellungen & Kupons
                case 'shopkunden':
                case 'bestellungen':
                    if ($option === 'shopkunden') {
                        $db->query('TRUNCATE tkunde');
                        $db->query('TRUNCATE tkundenattribut');
                        $db->query('TRUNCATE tkundendatenhistory');
                        $db->query('TRUNCATE tkundenfeld');
                        $db->query('TRUNCATE tkundenfeldwert');
                        $db->query('TRUNCATE tkundenherkunft');
                        $db->query('TRUNCATE tkundenkontodaten');
                        $db->query('TRUNCATE tlieferadresse');
                        $db->query('TRUNCATE trechnungsadresse');
                        $db->query('TRUNCATE twarenkorbpers');
                        $db->query('TRUNCATE twarenkorbperspos');
                        $db->query('TRUNCATE twarenkorbpersposeigenschaft');
                        $db->query('TRUNCATE twunschliste');
                        $db->query('TRUNCATE twunschlistepos');
                        $db->query('TRUNCATE twunschlisteposeigenschaft');
                        $db->query('TRUNCATE tpasswordreset');
                        $db->query('DELETE FROM tbesucher WHERE kKunde > 0');
                        $db->query('DELETE FROM tbesucherarchiv WHERE kKunde > 0');
                        $db->query(
                            'DELETE tbewertung, tbewertunghilfreich, tbewertungguthabenbonus
                                FROM tbewertung
                                LEFT JOIN tbewertunghilfreich
                                    ON tbewertunghilfreich.kBewertung = tbewertung.kBewertung
                                LEFT JOIN tbewertungguthabenbonus
                                    ON tbewertungguthabenbonus.kBewertung = tbewertung.kBewertung
                                WHERE tbewertung.kKunde > 0'
                        );
                        $db->query('DELETE FROM tgutschein WHERE kKunde > 0');
                        $db->query('DELETE FROM tnewskommentar WHERE kKunde > 0');
                        $db->query('DELETE FROM tnewsletterempfaenger WHERE kKunde > 0');
                        $db->query('DELETE FROM tnewsletterempfaengerhistory WHERE kKunde > 0');
                        $db->query(
                            'DELETE tpreis, tpreisdetail
                                FROM tpreis
                                LEFT JOIN tpreisdetail ON tpreisdetail.kPreis = tpreis.kPreis
                                WHERE kKunde > 0'
                        );
                    }
                    $db->query('TRUNCATE tbestellid');
                    $db->query('TRUNCATE tbestellstatus');
                    $db->query('TRUNCATE tbestellung');
                    $db->query('TRUNCATE tlieferschein');
                    $db->query('TRUNCATE tlieferscheinpos');
                    $db->query('TRUNCATE tlieferscheinposinfo');
                    $db->query('TRUNCATE twarenkorb');
                    $db->query('TRUNCATE twarenkorbpers');
                    $db->query('TRUNCATE twarenkorbperspos');
                    $db->query('TRUNCATE twarenkorbpersposeigenschaft');
                    $db->query('TRUNCATE twarenkorbpos');
                    $db->query('TRUNCATE twarenkorbposeigenschaft');
                    $db->query('TRUNCATE tuploaddatei');
                    $db->query('TRUNCATE tuploadqueue');
                    $db->query('TRUNCATE tzahlungsinfo');
                    $db->query('TRUNCATE trma');
                    $db->query('TRUNCATE trmaartikel');
                    $db->query('TRUNCATE tkuponbestellung');
                    $db->query('TRUNCATE tdownloadhistory');
                    $db->query('TRUNCATE tbestellattribut');
                    $db->query('TRUNCATE tzahlungseingang');
                    $db->query('TRUNCATE tzahlungsession');
                    $db->query('TRUNCATE tzahlungsid');
                    $db->query('TRUNCATE tzahlungslog');
                    $db->query('DELETE FROM tbesucher WHERE kBestellung > 0');
                    $db->query('DELETE FROM tbesucherarchiv WHERE kBestellung > 0');
                    $db->query('DELETE FROM tcheckboxlogging WHERE kBestellung > 0');

                    $uploadfiles = glob(PFAD_UPLOADS . '*');

                    foreach ($uploadfiles as $file) {
                        if (is_file($file) && mb_strpos($file, '.') !== 0) {
                            unlink($file);
                        }
                    }

                    break;
                case 'kupons':
                    $db->query('TRUNCATE tkupon');
                    $db->query('TRUNCATE tkuponbestellung');
                    $db->query('TRUNCATE tkuponkunde');
                    $db->query('TRUNCATE tkuponsprache');
                    break;
                case 'shopeinstellungen':
                    $db->query('TRUNCATE teinstellungenlog');
                    $db->query(
                        'UPDATE teinstellungen
                          INNER JOIN teinstellungen_default
                            USING(cName)
                          SET teinstellungen.cWert = teinstellungen_default.cWert'
                    );
                    break;
            }
        }
        Shop::Container()->getCache()->flushAll();
        $db->query('UPDATE tglobals SET dLetzteAenderung = NOW()');
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successShopReturn'), 'successShopReturn');
    } else {
        $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorChooseOption'), 'errorChooseOption');
    }

    executeHook(HOOK_BACKEND_SHOP_RESET_AFTER);
}

$smarty->display('shopzuruecksetzen.tpl');
