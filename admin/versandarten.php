<?php

use Illuminate\Support\Collection;
use JTL\Alert\Alert;
use JTL\Checkout\Versandart;
use JTL\Country\Country;
use JTL\Country\Manager;
use JTL\Customer\CustomerGroup;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Tax;
use JTL\Helpers\Text;
use JTL\Language\LanguageHelper;
use JTL\Pagination\Pagination;
use JTL\Plugin\Helper as PluginHelper;
use JTL\Services\JTL\CountryService;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('ORDER_SHIPMENT_VIEW', true, true);

require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'versandarten_inc.php';
Tax::setTaxRates();
$db              = Shop::Container()->getDB();
$defaultCurrency = $db->select('twaehrung', 'cStandard', 'Y');
$shippingType    = null;
$step            = 'uebersicht';
$shippingMethod  = null;
$taxRateKeys     = array_keys($_SESSION['Steuersatz']);
$alertHelper     = Shop::Container()->getAlertService();
$countryHelper   = Shop::Container()->getCountryService();
$languages       = LanguageHelper::getAllLanguages(0, true);
$getText         = Shop::Container()->getGetText();
$cache           = Shop::Container()->getCache();
$postData        = Text::filterXSS($_POST);
$postCountries   = $postData['land'] ?? [];
$manager         = new Manager($db, $smarty, $countryHelper, $cache, $alertHelper, $getText);

$missingShippingClassCombis = getMissingShippingClassCombi();
$smarty->assign('missingShippingClassCombis', $missingShippingClassCombis);

if (Form::validateToken()) {
    if (Request::postInt('neu') === 1 && Request::postInt('kVersandberechnung') > 0) {
        $step = 'neue Versandart';
    }
    if (Request::postInt('kVersandberechnung') > 0) {
        $shippingType = getShippingTypes(Request::verifyGPCDataInt('kVersandberechnung'));
    }

    if (Request::postInt('del') > 0) {
        $oldShippingMethod = $db->select('tversandart', 'kVersandart', (int)$postData['del']);
        Versandart::deleteInDB((int)$postData['del']);
        $manager->updateRegistrationCountries(explode(' ', trim($oldShippingMethod->cLaender ?? '')));
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successShippingMethodDelete'), 'successShippingMethodDelete');
        $cache->flushTags([CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]);
    }
    if (Request::postInt('edit') > 0) {
        $step                            = 'neue Versandart';
        $shippingMethod                  = $db->select('tversandart', 'kVersandart', Request::postInt('edit'));
        $VersandartZahlungsarten         = $db->selectAll(
            'tversandartzahlungsart',
            'kVersandart',
            Request::postInt('edit'),
            '*',
            'kZahlungsart'
        );
        $VersandartStaffeln              = $db->selectAll(
            'tversandartstaffel',
            'kVersandart',
            Request::postInt('edit'),
            '*',
            'fBis'
        );
        $shippingType                    = getShippingTypes((int)$shippingMethod->kVersandberechnung);
        $shippingMethod->cVersandklassen = trim($shippingMethod->cVersandklassen);

        $smarty->assign('VersandartZahlungsarten', reorganizeObjectArray($VersandartZahlungsarten, 'kZahlungsart'))
            ->assign('VersandartStaffeln', $VersandartStaffeln)
            ->assign('Versandart', $shippingMethod)
            ->assign('gewaehlteLaender', explode(' ', $shippingMethod->cLaender));
    }

    if (Request::postInt('clone') > 0) {
        $step = 'uebersicht';
        if (Versandart::cloneShipping($postData['clone'])) {
            $alertHelper->addAlert(
                Alert::TYPE_SUCCESS,
                __('successShippingMethodDuplicated'),
                'successShippingMethodDuplicated'
            );
            $cache->flushTags([CACHING_GROUP_OPTION]);
        } else {
            $alertHelper->addAlert(
                Alert::TYPE_ERROR,
                __('errorShippingMethodDuplicated'),
                'errorShippingMethodDuplicated'
            );
        }
    }

    if (isset($_GET['cISO']) && Request::getInt('zuschlag') === 1 && Request::getInt('kVersandart') > 0) {
        $step = 'Zuschlagsliste';

        $pagination = (new Pagination('surchargeList'))
            ->setRange(4)
            ->setItemArray((new Versandart(Request::getInt('kVersandart')))
                ->getShippingSurchargesForCountry($_GET['cISO']))
            ->assemble();

        $smarty->assign('surcharges', $pagination->getPageItems())
            ->assign('pagination', $pagination);
    }

    if (Request::postInt('neueVersandart') > 0) {
        $shippingMethod                           = new stdClass();
        $shippingMethod->cName                    = htmlspecialchars(
            $postData['cName'],
            ENT_COMPAT | ENT_HTML401,
            JTL_CHARSET
        );
        $shippingMethod->kVersandberechnung       = Request::postInt('kVersandberechnung');
        $shippingMethod->cAnzeigen                = $postData['cAnzeigen'];
        $shippingMethod->cBild                    = $postData['cBild'];
        $shippingMethod->nSort                    = Request::postInt('nSort');
        $shippingMethod->nMinLiefertage           = Request::postInt('nMinLiefertage');
        $shippingMethod->nMaxLiefertage           = Request::postInt('nMaxLiefertage');
        $shippingMethod->cNurAbhaengigeVersandart = $postData['cNurAbhaengigeVersandart'];
        $shippingMethod->cSendConfirmationMail    = $postData['cSendConfirmationMail'] ?? 'Y';
        $shippingMethod->cIgnoreShippingProposal  = $postData['cIgnoreShippingProposal'] ?? 'N';
        $shippingMethod->eSteuer                  = $postData['eSteuer'];
        $shippingMethod->fPreis                   = (float)str_replace(',', '.', $postData['fPreis'] ?? 0);
        // Versandkostenfrei ab X
        $shippingMethod->fVersandkostenfreiAbX = Request::postInt('versandkostenfreiAktiv') === 1
            ? (float)$postData['fVersandkostenfreiAbX']
            : 0;
        // Deckelung
        $shippingMethod->fDeckelung = Request::postInt('versanddeckelungAktiv') === 1
            ? (float)$postData['fDeckelung']
            : 0;

        $shippingMethod->cLaender = '';
        foreach (array_unique($postCountries) as $postIso) {
            $shippingMethod->cLaender .= $postIso . ' ';
        }

        $VersandartZahlungsarten = [];
        foreach (Request::verifyGPDataIntegerArray('kZahlungsart') as $kZahlungsart) {
            $versandartzahlungsart               = new stdClass();
            $versandartzahlungsart->kZahlungsart = $kZahlungsart;
            if ($postData['fAufpreis_' . $kZahlungsart] != 0) {
                $versandartzahlungsart->fAufpreis    = (float)str_replace(
                    ',',
                    '.',
                    $postData['fAufpreis_' . $kZahlungsart]
                );
                $versandartzahlungsart->cAufpreisTyp = $postData['cAufpreisTyp_' . $kZahlungsart];
            }
            $VersandartZahlungsarten[] = $versandartzahlungsart;
        }

        $lastScaleTo        = 0.0;
        $VersandartStaffeln = [];
        $upperLimits        = []; // Haelt alle fBis der Staffel
        $staffelDa          = true;
        if ($shippingType->cModulId === 'vm_versandberechnung_gewicht_jtl'
            || $shippingType->cModulId === 'vm_versandberechnung_warenwert_jtl'
            || $shippingType->cModulId === 'vm_versandberechnung_artikelanzahl_jtl'
        ) {
            $staffelDa = false;
            if (count($postData['bis']) > 0 && count($postData['preis']) > 0) {
                $staffelDa = true;
            }
            //preisstaffel beachten
            if (!isset($postData['bis'][0], $postData['preis'][0])
                || mb_strlen($postData['bis'][0]) === 0
                || mb_strlen($postData['preis'][0]) === 0
            ) {
                $staffelDa = false;
            }
            if (is_array($postData['bis']) && is_array($postData['preis'])) {
                foreach ($postData['bis'] as $i => $fBis) {
                    if (isset($postData['preis'][$i]) && mb_strlen($fBis) > 0) {
                        unset($oVersandstaffel);
                        $oVersandstaffel         = new stdClass();
                        $oVersandstaffel->fBis   = (float)str_replace(',', '.', $fBis);
                        $oVersandstaffel->fPreis = (float)str_replace(',', '.', $postData['preis'][$i]);

                        $VersandartStaffeln[] = $oVersandstaffel;
                        $upperLimits[]        = $oVersandstaffel->fBis;
                        $lastScaleTo          = $oVersandstaffel->fBis;
                    }
                }
            }
            // Dummy Versandstaffel hinzufuegen, falls Versandart nach Warenwert und Versandkostenfrei ausgewaehlt wurde
            if ($shippingType->cModulId === 'vm_versandberechnung_warenwert_jtl'
                && Request::postInt('versandkostenfreiAktiv') === 1
            ) {
                $shippingMethod->fVersandkostenfreiAbX = $lastScaleTo + 0.01;

                $oVersandstaffel         = new stdClass();
                $oVersandstaffel->fBis   = 999999999;
                $oVersandstaffel->fPreis = 0.0;
                $VersandartStaffeln[]    = $oVersandstaffel;
            }
        }
        // Kundengruppe
        $shippingMethod->cKundengruppen = '';
        if (!isset($postData['kKundengruppe'])) {
            $postData['kKundengruppe'] = [-1];
        }
        if (is_array($postData['kKundengruppe'])) {
            if (in_array(-1, $postData['kKundengruppe'])) {
                $shippingMethod->cKundengruppen = '-1';
            } else {
                $shippingMethod->cKundengruppen = ';' . implode(';', $postData['kKundengruppe']) . ';';
            }
        }
        // Versandklassen
        $shippingMethod->cVersandklassen = ((!empty($postData['kVersandklasse']) && $postData['kVersandklasse'] !== '-1')
            ? ' ' . $postData['kVersandklasse'] . ' '
            : '-1');

        if (count($postCountries) >= 1
            && count($postData['kZahlungsart'] ?? []) >= 1
            && $shippingMethod->cName
            && $staffelDa
        ) {
            $methodID = 0;
            if (Request::postInt('kVersandart') === 0) {
                $methodID = $db->insert('tversandart', $shippingMethod);
                $alertHelper->addAlert(
                    Alert::TYPE_SUCCESS,
                    sprintf(__('successShippingMethodCreate'), $shippingMethod->cName),
                    'successShippingMethodCreate'
                );
            } else {
                //updaten
                $methodID          = Request::postInt('kVersandart');
                $oldShippingMethod = $db->select('tversandart', 'kVersandart', $methodID);
                $db->update('tversandart', 'kVersandart', $methodID, $shippingMethod);
                $db->delete('tversandartzahlungsart', 'kVersandart', $methodID);
                $db->delete('tversandartstaffel', 'kVersandart', $methodID);
                $alertHelper->addAlert(
                    Alert::TYPE_SUCCESS,
                    sprintf(__('successShippingMethodChange'), $shippingMethod->cName),
                    'successShippingMethodChange'
                );
            }
            $manager->updateRegistrationCountries(
                array_diff(
                    isset($oldShippingMethod->cLaender)
                        ? explode(' ', trim($oldShippingMethod->cLaender))
                        : [],
                    $postCountries
                )
            );
            if ($methodID > 0) {
                foreach ($VersandartZahlungsarten as $versandartzahlungsart) {
                    $versandartzahlungsart->kVersandart = $methodID;
                    $db->insert('tversandartzahlungsart', $versandartzahlungsart);
                }

                foreach ($VersandartStaffeln as $versandartstaffel) {
                    $versandartstaffel->kVersandart = $methodID;
                    $db->insert('tversandartstaffel', $versandartstaffel);
                }
                $versandSprache = new stdClass();

                $versandSprache->kVersandart = $methodID;
                foreach ($languages as $language) {
                    $code = $language->getCode();

                    $versandSprache->cISOSprache = $code;
                    $versandSprache->cName       = $shippingMethod->cName;
                    if ($postData['cName_' . $code]) {
                        $versandSprache->cName = htmlspecialchars(
                            $postData['cName_' . $code],
                            ENT_COMPAT | ENT_HTML401,
                            JTL_CHARSET
                        );
                    }
                    $versandSprache->cLieferdauer = '';
                    if ($postData['cLieferdauer_' . $code]) {
                        $versandSprache->cLieferdauer = htmlspecialchars(
                            $postData['cLieferdauer_' . $code],
                            ENT_COMPAT | ENT_HTML401,
                            JTL_CHARSET
                        );
                    }
                    $versandSprache->cHinweistext = '';
                    if ($postData['cHinweistext_' . $code]) {
                        $versandSprache->cHinweistext = $postData['cHinweistext_' . $code];
                    }
                    $versandSprache->cHinweistextShop = '';
                    if ($postData['cHinweistextShop_' . $code]) {
                        $versandSprache->cHinweistextShop = $postData['cHinweistextShop_' . $code];
                    }
                    $db->delete('tversandartsprache', ['kVersandart', 'cISOSprache'], [$methodID, $code]);
                    $db->insert('tversandartsprache', $versandSprache);
                }
                $step = 'uebersicht';
            }
            $cache->flushTags([CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]);
        } else {
            $step = 'neue Versandart';
            if (!$shippingMethod->cName) {
                $alertHelper->addAlert(
                    Alert::TYPE_ERROR,
                    __('errorShippingMethodNameMissing'),
                    'errorShippingMethodNameMissing'
                );
            }
            if (count($postCountries) < 1) {
                $alertHelper->addAlert(
                    Alert::TYPE_ERROR,
                    __('errorShippingMethodCountryMissing'),
                    'errorShippingMethodCountryMissing'
                );
            }
            if (count($postData['kZahlungsart'] ?? []) < 1) {
                $alertHelper->addAlert(
                    Alert::TYPE_ERROR,
                    __('errorShippingMethodPaymentMissing'),
                    'errorShippingMethodPaymentMissing'
                );
            }
            if (!$staffelDa) {
                $alertHelper->addAlert(
                    Alert::TYPE_ERROR,
                    __('errorShippingMethodPriceMissing'),
                    'errorShippingMethodPriceMissing'
                );
            }
            if (Request::postInt('kVersandart') > 0) {
                $shippingMethod = $db->select('tversandart', 'kVersandart', Request::postInt('kVersandart'));
            }
            $smarty->assign('VersandartZahlungsarten', reorganizeObjectArray($VersandartZahlungsarten, 'kZahlungsart'))
                ->assign('VersandartStaffeln', $VersandartStaffeln)
                ->assign('Versandart', $shippingMethod)
                ->assign('gewaehlteLaender', explode(' ', $shippingMethod->cLaender));
        }
    }
    $cache->flush(CountryService::CACHE_ID);
}
if ($step === 'neue Versandart') {
    $versandlaender = $countryHelper->getCountrylist();
    if ($shippingType->cModulId === 'vm_versandberechnung_gewicht_jtl') {
        $smarty->assign('einheit', 'kg');
    }
    if ($shippingType->cModulId === 'vm_versandberechnung_warenwert_jtl') {
        $smarty->assign('einheit', $defaultCurrency->cName);
    }
    if ($shippingType->cModulId === 'vm_versandberechnung_artikelanzahl_jtl') {
        $smarty->assign('einheit', 'Stück');
    }
    // prevent "unusable" payment methods from displaying them in the config section (mainly the null-payment)
    $zahlungsarten = $db->selectAll(
        'tzahlungsart',
        ['nActive', 'nNutzbar'],
        [1, 1],
        '*',
        'cAnbieter, nSort, cName, cModulId'
    );
    foreach ($zahlungsarten as $zahlungsart) {
        $pluginID = PluginHelper::getIDByModuleID($zahlungsart->cModulId);
        if ($pluginID > 0) {
            try {
                Shop::Container()->getGetText()->loadPluginLocale(
                    'base',
                    PluginHelper::getLoaderByPluginID($pluginID)->init($pluginID)
                );
            } catch (InvalidArgumentException $e) {
                $getText->loadAdminLocale('pages/zahlungsarten');
                $alertHelper->addAlert(
                    Alert::TYPE_WARNING,
                    sprintf(
                        __('Plugin for payment method not found'),
                        $zahlungsart->cName,
                        $zahlungsart->cAnbieter
                    ),
                    'notfound_' . $pluginID,
                    [
                        'linkHref' => Shop::getAdminURL(true) . '/zahlungsarten.php',
                        'linkText' => __('paymentTypesOverview')
                    ]
                );
                continue;
            }
        }
        $zahlungsart->cName     = __($zahlungsart->cName);
        $zahlungsart->cAnbieter = __($zahlungsart->cAnbieter);
    }
    $tmpID = (int)($shippingMethod->kVersandart ?? 0);
    $smarty->assign('versandKlassen', $db->selectAll('tversandklasse', [], [], '*', 'kVersandklasse'))
        ->assign('zahlungsarten', $zahlungsarten)
        ->assign('versandlaender', $versandlaender)
        ->assign('continents', $countryHelper->getCountriesGroupedByContinent(
            true,
            explode(' ', $shippingMethod->cLaender ?? '')
        ))
        ->assign('versandberechnung', $shippingType)
        ->assign('waehrung', $defaultCurrency->cName)
        ->assign('customerGroups', CustomerGroup::getGroups())
        ->assign('oVersandartSpracheAssoc_arr', getShippingLanguage($tmpID, $languages))
        ->assign('gesetzteVersandklassen', isset($shippingMethod->cVersandklassen)
            ? gibGesetzteVersandklassen($shippingMethod->cVersandklassen)
            : null)
        ->assign('gesetzteKundengruppen', isset($shippingMethod->cKundengruppen)
            ? gibGesetzteKundengruppen($shippingMethod->cKundengruppen)
            : null);
}
if ($step === 'uebersicht') {
    $customerGroups  = $db->getObjects('SELECT kKundengruppe, cName FROM tkundengruppe ORDER BY kKundengruppe');
    $shippingMethods = $db->getObjects('SELECT * FROM tversandart ORDER BY nSort, cName');
    foreach ($shippingMethods as $method) {
        $method->versandartzahlungsarten = $db->getObjects(
            'SELECT tversandartzahlungsart.*
                FROM tversandartzahlungsart
                JOIN tzahlungsart
                    ON tzahlungsart.kZahlungsart = tversandartzahlungsart.kZahlungsart
                WHERE tversandartzahlungsart.kVersandart = :sid
                ORDER BY tzahlungsart.cAnbieter, tzahlungsart.nSort, tzahlungsart.cName',
            ['sid' => (int)$method->kVersandart]
        );

        foreach ($method->versandartzahlungsarten as $smp) {
            $smp->zahlungsart  = $db->select(
                'tzahlungsart',
                'kZahlungsart',
                (int)$smp->kZahlungsart,
                'nActive',
                1
            );
            $smp->cAufpreisTyp = $smp->cAufpreisTyp === 'prozent' ? '%' : '';
            $pluginID          = PluginHelper::getIDByModuleID($smp->zahlungsart->cModulId);
            if ($pluginID > 0) {
                try {
                    $getText->loadPluginLocale(
                        'base',
                        PluginHelper::getLoaderByPluginID($pluginID)->init($pluginID)
                    );
                } catch (InvalidArgumentException $e) {
                    $getText->loadAdminLocale('pages/zahlungsarten');
                    $alertHelper->addAlert(
                        Alert::TYPE_WARNING,
                        sprintf(
                            __('Plugin for payment method not found'),
                            $smp->zahlungsart->cName,
                            $smp->zahlungsart->cAnbieter
                        ),
                        'notfound_' . $pluginID,
                        [
                            'linkHref' => Shop::getAdminURL(true) . '/zahlungsarten.php',
                            'linkText' => __('paymentTypesOverview')
                        ]
                    );
                    continue;
                }
            }
            $smp->zahlungsart->cName     = __($smp->zahlungsart->cName);
            $smp->zahlungsart->cAnbieter = __($smp->zahlungsart->cAnbieter);
        }
        $method->versandartstaffeln         = $db->selectAll(
            'tversandartstaffel',
            'kVersandart',
            (int)$method->kVersandart,
            '*',
            'fBis'
        );
        $method->fPreisBrutto               = berechneVersandpreisBrutto(
            $method->fPreis,
            $_SESSION['Steuersatz'][$taxRateKeys[0]]
        );
        $method->fVersandkostenfreiAbXNetto = berechneVersandpreisNetto(
            $method->fVersandkostenfreiAbX,
            $_SESSION['Steuersatz'][$taxRateKeys[0]]
        );
        $method->fDeckelungBrutto           = berechneVersandpreisBrutto(
            $method->fDeckelung,
            $_SESSION['Steuersatz'][$taxRateKeys[0]]
        );
        foreach ($method->versandartstaffeln as $j => $oVersandartstaffeln) {
            $method->versandartstaffeln[$j]->fPreisBrutto = berechneVersandpreisBrutto(
                $oVersandartstaffeln->fPreis,
                $_SESSION['Steuersatz'][$taxRateKeys[0]]
            );
        }

        $method->versandberechnung = getShippingTypes((int)$method->kVersandberechnung);
        $method->versandklassen    = gibGesetzteVersandklassenUebersicht($method->cVersandklassen);
        if ($method->versandberechnung->cModulId === 'vm_versandberechnung_gewicht_jtl') {
            $method->einheit = 'kg';
        }
        if ($method->versandberechnung->cModulId === 'vm_versandberechnung_warenwert_jtl') {
            $method->einheit = $defaultCurrency->cName;
        }
        if ($method->versandberechnung->cModulId === 'vm_versandberechnung_artikelanzahl_jtl') {
            $method->einheit = 'Stück';
        }
        $method->countries                  = new Collection();
        $method->shippingSurchargeCountries = array_column($db->getArrays(
            'SELECT DISTINCT cISO FROM tversandzuschlag WHERE kVersandart = :shippingMethodID',
            ['shippingMethodID' => (int)$method->kVersandart]
        ), 'cISO');
        foreach (explode(' ', trim($method->cLaender)) as $item) {
            if (($country = $countryHelper->getCountry($item)) !== null) {
                $method->countries->push($country);
            }
        }
        $method->countries               = $method->countries->sortBy(static function (Country $country) {
            return $country->getName();
        });
        $method->cKundengruppenName_arr  = [];
        $method->oVersandartSprachen_arr = $db->selectAll(
            'tversandartsprache',
            'kVersandart',
            (int)$method->kVersandart,
            'cName',
            'cISOSprache'
        );
        foreach (Text::parseSSKint($method->cKundengruppen) as $customerGroupID) {
            if ($customerGroupID === -1) {
                $method->cKundengruppenName_arr[] = __('allCustomerGroups');
            } else {
                foreach ($customerGroups as $customerGroup) {
                    if ((int)$customerGroup->kKundengruppe === $customerGroupID) {
                        $method->cKundengruppenName_arr[] = $customerGroup->cName;
                    }
                }
            }
        }
    }

    $missingShippingClassCombis = getMissingShippingClassCombi();
    if (!empty($missingShippingClassCombis)) {
        $errorMissingShippingClassCombis = $smarty->assign('missingShippingClassCombis', $missingShippingClassCombis)
            ->fetch('tpl_inc/versandarten_fehlende_kombis.tpl');
        $alertHelper->addAlert(Alert::TYPE_ERROR, $errorMissingShippingClassCombis, 'errorMissingShippingClassCombis');
    }

    $smarty->assign('versandberechnungen', getShippingTypes())
        ->assign('versandarten', $shippingMethods)
        ->assign('waehrung', $defaultCurrency->cName);
}
if ($step === 'Zuschlagsliste') {
    $iso      = $_GET['cISO'] ?? $postData['cISO'] ?? null;
    $methodID = Request::getInt('kVersandart');
    if (isset($postData['kVersandart'])) {
        $methodID = Request::postInt('kVersandart');
    }
    $shippingMethod = $db->select('tversandart', 'kVersandart', $methodID);
    $fees           = $db->selectAll(
        'tversandzuschlag',
        ['kVersandart', 'cISO'],
        [(int)$shippingMethod->kVersandart, $iso],
        '*',
        'fZuschlag'
    );
    foreach ($fees as $item) {
        $item->zuschlagplz     = $db->selectAll(
            'tversandzuschlagplz',
            'kVersandzuschlag',
            $item->kVersandzuschlag
        );
        $item->angezeigterName = getZuschlagNames($item->kVersandzuschlag);
    }
    $smarty->assign('Versandart', $shippingMethod)
        ->assign('Zuschlaege', $fees)
        ->assign('waehrung', $defaultCurrency->cName)
        ->assign('Land', $countryHelper->getCountry($iso));
}

$smarty->assign('fSteuersatz', $_SESSION['Steuersatz'][$taxRateKeys[0]])
    ->assign('oWaehrung', $db->select('twaehrung', 'cStandard', 'Y'))
    ->assign('step', $step)
    ->display('versandarten.tpl');
