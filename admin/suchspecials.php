<?php

use JTL\Alert\Alert;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Seo;
use JTL\Helpers\Text;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('SETTINGS_SPECIALPRODUCTS_VIEW', true, true);
$step        = 'suchspecials';
$db          = Shop::Container()->getDB();
$alertHelper = Shop::Container()->getAlertService();
setzeSprache();
$languageID = (int)$_SESSION['editLanguageID'];
$postData   = Text::filterXSS($_POST);
if (Request::verifyGPCDataInt('einstellungen') === 1) {
    $alertHelper->addAlert(
        Alert::TYPE_SUCCESS,
        saveAdminSectionSettings(CONF_SUCHSPECIAL, $_POST),
        'saveSettings'
    );
} elseif (Request::postInt('suchspecials') === 1 && Form::validateToken()) {
    $searchSpecials   = $db->selectAll(
        'tseo',
        ['cKey', 'kSprache'],
        ['suchspecial', $languageID],
        '*',
        'kKey'
    );
    $ssTmp            = [];
    $ssToDelete       = [];
    $bestSellerSeo    = strip_tags($db->escape($postData['bestseller']));
    $specialOffersSeo = $db->escape($postData['sonderangebote']);
    $newProductsSeo   = strip_tags($db->escape($postData['neu_im_sortiment']));
    $topOffersSeo     = strip_tags($db->escape($postData['top_angebote']));
    $releaseSeo       = strip_tags($db->escape($postData['in_kuerze_verfuegbar']));
    $topRatedSeo      = strip_tags($db->escape($postData['top_bewertet']));
    if (mb_strlen($bestSellerSeo) > 0 && !pruefeSuchspecialSeo(
        $searchSpecials,
        $bestSellerSeo,
        SEARCHSPECIALS_BESTSELLER
    )) {
        $bestSellerSeo = Seo::checkSeo(Seo::getSeo($bestSellerSeo));

        if ($bestSellerSeo !== $postData['bestseller']) {
            $alertHelper->addAlert(
                Alert::TYPE_NOTE,
                sprintf(
                    __('errorBestsellerExistRename'),
                    $postData['bestseller'],
                    $bestSellerSeo
                ),
                'errorBestsellerExistRename'
            );
        }
        $bestSeller       = new stdClass();
        $bestSeller->kKey = SEARCHSPECIALS_BESTSELLER;
        $bestSeller->cSeo = $bestSellerSeo;

        $ssTmp[] = $bestSeller;
    } elseif (mb_strlen($bestSellerSeo) === 0) {
        $ssToDelete[] = SEARCHSPECIALS_BESTSELLER;
    }
    // Pruefe Sonderangebote
    if (mb_strlen($specialOffersSeo) > 0 && !pruefeSuchspecialSeo(
        $searchSpecials,
        $specialOffersSeo,
        SEARCHSPECIALS_SPECIALOFFERS
    )) {
        $specialOffersSeo = Seo::checkSeo(Seo::getSeo($specialOffersSeo));

        if ($specialOffersSeo !== $postData['sonderangebote']) {
            $alertHelper->addAlert(
                Alert::TYPE_NOTE,
                sprintf(
                    __('errorSpecialExistRename'),
                    $postData['sonderangebote'],
                    $specialOffersSeo
                ),
                'errorSpecialExistRename'
            );
        }
        $specialOffer       = new stdClass();
        $specialOffer->kKey = SEARCHSPECIALS_SPECIALOFFERS;
        $specialOffer->cSeo = $specialOffersSeo;

        $ssTmp[] = $specialOffer;
    } elseif (mb_strlen($specialOffersSeo) === 0) {
        // cSeo loeschen
        $ssToDelete[] = SEARCHSPECIALS_SPECIALOFFERS;
    }
    // Pruefe Neu im Sortiment
    if (mb_strlen($newProductsSeo) > 0 && !pruefeSuchspecialSeo(
        $searchSpecials,
        $newProductsSeo,
        SEARCHSPECIALS_NEWPRODUCTS
    )) {
        $newProductsSeo = Seo::checkSeo(Seo::getSeo($newProductsSeo));

        if ($newProductsSeo !== $postData['neu_im_sortiment']) {
            $alertHelper->addAlert(
                Alert::TYPE_NOTE,
                sprintf(
                    __('errorNewExistRename'),
                    $postData['neu_im_sortiment'],
                    $newProductsSeo
                ),
                'errorNewExistRename'
            );
        }
        $newProducts       = new stdClass();
        $newProducts->kKey = SEARCHSPECIALS_NEWPRODUCTS;
        $newProducts->cSeo = $newProductsSeo;

        $ssTmp[] = $newProducts;
    } elseif (mb_strlen($newProductsSeo) === 0) {
        // cSeo leoschen
        $ssToDelete[] = SEARCHSPECIALS_NEWPRODUCTS;
    }
    // Pruefe Top Angebote
    if (mb_strlen($topOffersSeo) > 0 && !pruefeSuchspecialSeo(
        $searchSpecials,
        $topOffersSeo,
        SEARCHSPECIALS_TOPOFFERS
    )) {
        $topOffersSeo = Seo::checkSeo(Seo::getSeo($topOffersSeo));

        if ($topOffersSeo !== $postData['top_angebote']) {
            $alertHelper->addAlert(
                Alert::TYPE_NOTE,
                sprintf(
                    __('errorTopProductsExistRename'),
                    $postData['top_angebote'],
                    $topOffersSeo
                ),
                'errorTopProductsExistRename'
            );
        }
        $topOffers       = new stdClass();
        $topOffers->kKey = SEARCHSPECIALS_TOPOFFERS;
        $topOffers->cSeo = $topOffersSeo;

        $ssTmp[] = $topOffers;
    } elseif (mb_strlen($topOffersSeo) === 0) {
        // cSeo loeschen
        $ssToDelete[] = SEARCHSPECIALS_TOPOFFERS;
    }
    // Pruefe In kuerze Verfuegbar
    if (mb_strlen($releaseSeo) > 0 && !pruefeSuchspecialSeo(
        $searchSpecials,
        $releaseSeo,
        SEARCHSPECIALS_UPCOMINGPRODUCTS
    )) {
        $releaseSeo = Seo::checkSeo(Seo::getSeo($releaseSeo));
        if ($releaseSeo !== $postData['in_kuerze_verfuegbar']) {
            $alertHelper->addAlert(
                Alert::TYPE_NOTE,
                sprintf(
                    __('errorSoonExistRename'),
                    $postData['in_kuerze_verfuegbar'],
                    $releaseSeo
                ),
                'errorSoonExistRename'
            );
        }
        $release       = new stdClass();
        $release->kKey = SEARCHSPECIALS_UPCOMINGPRODUCTS;
        $release->cSeo = $releaseSeo;

        $ssTmp[] = $release;
    } elseif (mb_strlen($releaseSeo) === 0) {
        // cSeo loeschen
        $ssToDelete[] = SEARCHSPECIALS_UPCOMINGPRODUCTS;
    }
    // Pruefe Top bewertet
    if (mb_strlen($topRatedSeo) > 0 && !pruefeSuchspecialSeo(
        $searchSpecials,
        $topRatedSeo,
        SEARCHSPECIALS_TOPREVIEWS
    )) {
        $topRatedSeo = Seo::checkSeo(Seo::getSeo($topRatedSeo));

        if ($topRatedSeo !== $postData['top_bewertet']) {
            $alertHelper->addAlert(
                Alert::TYPE_NOTE,
                sprintf(
                    __('errorTopRatingExistRename'),
                    $postData['top_bewertet'],
                    $topRatedSeo
                ),
                'errorTopRatingExistRename'
            );
        }
        $topRated       = new stdClass();
        $topRated->kKey = SEARCHSPECIALS_TOPREVIEWS;
        $topRated->cSeo = $topRatedSeo;

        $ssTmp[] = $topRated;
    } elseif (mb_strlen($topRatedSeo) === 0) {
        // cSeo loeschen
        $ssToDelete[] = SEARCHSPECIALS_TOPREVIEWS;
    }
    // tseo speichern
    if (count($ssTmp) > 0) {
        $ids = [];
        foreach ($ssTmp as $i => $item) {
            $ids[] = (int)$item->kKey;
        }
        $db->query(
            "DELETE FROM tseo
                WHERE cKey = 'suchspecial'
                    AND kSprache = " . $languageID . '
                    AND kKey IN (' . implode(',', $ids) . ')'
        );
        foreach ($ssTmp as $item) {
            $seo           = new stdClass();
            $seo->cSeo     = $item->cSeo;
            $seo->cKey     = 'suchspecial';
            $seo->kKey     = $item->kKey;
            $seo->kSprache = $languageID;

            $db->insert('tseo', $seo);
        }
    }
    if (count($ssToDelete) > 0) {
        $db->query(
            "DELETE FROM tseo
                WHERE cKey = 'suchspecial'
                    AND kSprache = " . $languageID . '
                    AND kKey IN (' . implode(',', $ssToDelete) . ')'
        );
    }
    $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successSeoSave'), 'successSeoSave');
}

$ssSeoData      = $db->selectAll(
    'tseo',
    ['cKey', 'kSprache'],
    ['suchspecial', $languageID],
    '*',
    'kKey'
);
$searchSpecials = [];
foreach ($ssSeoData as $searchSpecial) {
    $searchSpecials[$searchSpecial->kKey] = $searchSpecial->cSeo;
}

$smarty->assign('oConfig_arr', getAdminSectionSettings(CONF_SUCHSPECIAL))
    ->assign('oSuchSpecials_arr', $searchSpecials)
    ->assign('step', $step)
    ->display('suchspecials.tpl');

/**
 * Prueft ob ein bestimmtes Suchspecial Seo schon vorhanden ist
 *
 * @param array  $searchSpecials
 * @param string $seo
 * @param int    $key
 * @return bool
 */
function pruefeSuchspecialSeo(array $searchSpecials, string $seo, int $key): bool
{
    if ($key > 0 && count($searchSpecials) > 0 && mb_strlen($seo)) {
        foreach ($searchSpecials as $special) {
            if ((int)$special->kKey === $key && $special->cSeo === $seo) {
                return true;
            }
        }
    }

    return false;
}
