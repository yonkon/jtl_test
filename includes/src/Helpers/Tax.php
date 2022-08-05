<?php

namespace JTL\Helpers;

use JTL\Alert\Alert;
use JTL\Cart\Cart;
use JTL\Catalog\Currency;
use JTL\Catalog\Product\Preise;
use JTL\Language\LanguageHelper;
use JTL\Link\Link;
use JTL\Session\Frontend;
use JTL\Shop;
use stdClass;
use function Functional\map;

/**
 * Class Tax
 * @package JTL\Helpers
 * @since since 5.0.0
 */
class Tax
{
    /**
     * @param int $taxID
     * @return mixed
     * @since since 5.0.0
     */
    public static function getSalesTax(int $taxID)
    {
        if (!GeneralObject::hasCount('Steuersatz', $_SESSION)) {
            self::setTaxRates();
        }
        if (GeneralObject::isCountable('Steuersatz', $_SESSION) && !isset($_SESSION['Steuersatz'][$taxID])) {
            $taxID = (int)(\array_keys($_SESSION['Steuersatz'])[0] ?? 0);
        }

        return $_SESSION['Steuersatz'][$taxID] ?? 0;
    }

    /**
     * @param string|null $countryCode
     * @param bool $skipUpdateCart
     * @since 5.0.0
     */
    public static function setTaxRates($countryCode = null, bool $skipUpdateCart = false): void
    {
        $_SESSION['Steuersatz'] = [];
        $billingCountryCode     = null;
        $merchantCountryCode    = 'DE';
        $db                     = Shop::Container()->getDB();
        $company                = $db->getSingleObject('SELECT cLand FROM tfirma');
        if ($company !== null && !empty($company->cLand)) {
            $merchantCountryCode = LanguageHelper::getIsoCodeByCountryName($company->cLand);
        }
        if (\defined('STEUERSATZ_STANDARD_LAND')) {
            $merchantCountryCode = STEUERSATZ_STANDARD_LAND;
        }
        $deliveryCountryCode = $merchantCountryCode;
        if (!empty(Frontend::getCustomer()->cLand)) {
            $deliveryCountryCode = Frontend::getCustomer()->cLand;
            $billingCountryCode  = Frontend::getCustomer()->cLand;
        }
        if (!empty($_SESSION['Lieferadresse']->cLand)) {
            $deliveryCountryCode = $_SESSION['Lieferadresse']->cLand;
        }
        if (!empty($_SESSION['preferredDeliveryCountryCode'])) {
            $deliveryCountryCode = $_SESSION['preferredDeliveryCountryCode'];
        }
        if ($countryCode) {
            $deliveryCountryCode = $countryCode;
        }
        if ($billingCountryCode === null) {
            $billingCountryCode = $deliveryCountryCode;
        }
        $_SESSION['Steuerland']     = $deliveryCountryCode;
        $_SESSION['cLieferlandISO'] = $deliveryCountryCode;

        // Pruefen, ob Voraussetzungen fuer innergemeinschaftliche Lieferung (IGL) erfuellt werden #3525
        // Bedingungen fuer Steuerfreiheit bei Lieferung in EU-Ausland:
        // Kunde hat eine zum Rechnungland passende, gueltige USt-ID gesetzt &&
        // Firmen-Land != Kunden-Rechnungsland && Firmen-Land != Kunden-Lieferland
        $UstBefreiungIGL = false;
        if ($merchantCountryCode !== $deliveryCountryCode
            && $merchantCountryCode !== $billingCountryCode
            && !empty(Frontend::getCustomer()->cUSTID)
            && (\strcasecmp($billingCountryCode, \mb_substr(Frontend::getCustomer()->cUSTID, 0, 2)) === 0
                || (\strcasecmp($billingCountryCode, 'GR') === 0
                    && \strcasecmp(\mb_substr(Frontend::getCustomer()->cUSTID, 0, 2), 'EL') === 0))
        ) {
            $countryHelper   = Shop::Container()->getCountryService();
            $deliveryCountry = $countryHelper->getCountry($deliveryCountryCode);
            $shopCountry     = $countryHelper->getCountry($merchantCountryCode);
            if ($deliveryCountry !== null
                && $shopCountry !== null
                && $deliveryCountry->isEU()
                && $shopCountry->isEU()
            ) {
                $UstBefreiungIGL = true;
            }
        }
        $taxZones = $db->getObjects(
            'SELECT tsteuerzone.kSteuerzone
                FROM tsteuerzone, tsteuerzoneland
                WHERE tsteuerzoneland.cISO = :ciso
                    AND tsteuerzoneland.kSteuerzone = tsteuerzone.kSteuerzone',
            ['ciso' => $deliveryCountryCode]
        );
        if (\count($taxZones) === 0) {
            // Keine Steuerzone für $deliveryCountryCode hinterlegt - das ist fatal!
            $linkService = Shop::Container()->getLinkService();
            $logoutURL   = $linkService->getStaticRoute('jtl.php') . '?logout=1';
            $redirURL    = Frontend::getCustomer()->isLoggedIn()
                ? $linkService->getStaticRoute('jtl.php') . '?editRechnungsadresse=1'
                : $linkService->getStaticRoute('bestellvorgang.php') . '?editRechnungsadresse=1';
            $currentURL  = (new URL(Shop::getURL() . $_SERVER['REQUEST_URI']))->normalize();
            $country     = LanguageHelper::getCountryCodeByCountryName($deliveryCountryCode);

            Shop::Container()->getLogService()->error('Keine Steuerzone für "' . $country . '" hinterlegt!');

            if (Request::isAjaxRequest()) {
                $link = new Link($db);
                $link->setLinkType(\LINKTYP_STARTSEITE);
                $link->setTitle(Shop::Lang()->get('missingParamShippingDetermination', 'errorMessages'));

                Shop::Container()->getAlertService()->addAlert(
                    Alert::TYPE_ERROR,
                    Shop::Lang()->get('missingTaxZoneForDeliveryCountry', 'errorMessages', $country),
                    'missingTaxZoneForDeliveryCountry'
                );
                Shop::Smarty()
                    ->assign('Link', $link)
                    ->display('layout/index.tpl');
                exit;
            }

            if (\in_array($currentURL, [$redirURL, $logoutURL])) {
                Shop::Container()->getAlertService()->addAlert(
                    Alert::TYPE_ERROR,
                    Shop::Lang()->get('missingParamShippingDetermination', 'errorMessages') . '<br/>'
                    . Shop::Lang()->get('missingTaxZoneForDeliveryCountry', 'errorMessages', $country),
                    'missingParamShippingDetermination'
                );

                return;
            }

            \header('Location: ' . $redirURL);
            exit;
        }
        $zones = map($taxZones, static function ($e) {
            return (int)$e->kSteuerzone;
        });
        $qry   = \count($zones) > 0
            ? 'kSteuerzone IN (' . \implode(',', $zones) . ')'
            : '';

        if ($qry !== '') {
            $taxClasses = $db->getObjects('SELECT * FROM tsteuerklasse');
            foreach ($taxClasses as $taxClass) {
                $taxClass->kSteuerklasse                          = (int)$taxClass->kSteuerklasse;
                $rate                                             = $db->getSingleObject(
                    'SELECT fSteuersatz
                        FROM tsteuersatz
                        WHERE kSteuerklasse = :tcid
                        AND (' . $qry . ') ORDER BY nPrio DESC',
                    ['tcid' => $taxClass->kSteuerklasse]
                );
                $_SESSION['Steuersatz'][$taxClass->kSteuerklasse] = $rate->fSteuersatz ?? 0;
                if ($UstBefreiungIGL) {
                    $_SESSION['Steuersatz'][$taxClass->kSteuerklasse] = 0;
                }
            }
        }
        if ($skipUpdateCart === false && isset($_SESSION['Warenkorb']) && $_SESSION['Warenkorb'] instanceof Cart) {
            Frontend::getCart()->setzePositionsPreise();
        }
    }

    /**
     * @param array                  $items
     * @param int|bool               $net
     * @param bool                   $html
     * @param Currency|stdClass|null $currency
     * @return array
     * @former gibAlteSteuerpositionen()
     * @since since 5.0.0
     */
    public static function getOldTaxItems(array $items, $net = -1, $html = true, $currency = null): array
    {
        if ($net === -1) {
            $net = Frontend::getCustomerGroup()->isMerchant();
        }
        $taxRates = [];
        $taxPos   = [];
        if (Shop::getSettingValue(\CONF_GLOBAL, 'global_steuerpos_anzeigen') === 'N') {
            return $taxPos;
        }
        foreach ($items as $item) {
            if ($item->fMwSt > 0 && !\in_array($item->fMwSt, $taxRates, true)) {
                $taxRates[] = $item->fMwSt;
            }
        }
        \sort($taxRates);
        foreach ($items as $item) {
            if ($item->fMwSt <= 0) {
                continue;
            }
            $i = \array_search($item->fMwSt, $taxRates, true);
            if (!isset($taxPos[$i]->fBetrag) || !$taxPos[$i]->fBetrag) {
                $taxPos[$i]                  = new stdClass();
                $taxPos[$i]->cName           = \lang_steuerposition($item->fMwSt, $net);
                $taxPos[$i]->fUst            = $item->fMwSt;
                $taxPos[$i]->fBetrag         = ($item->fPreis * $item->nAnzahl * $item->fMwSt) / 100.0;
                $taxPos[$i]->cPreisLocalized = Preise::getLocalizedPriceString($taxPos[$i]->fBetrag, $currency, $html);
            } else {
                $taxPos[$i]->fBetrag        += ($item->fPreis * $item->nAnzahl * $item->fMwSt) / 100.0;
                $taxPos[$i]->cPreisLocalized = Preise::getLocalizedPriceString($taxPos[$i]->fBetrag, $currency, $html);
            }
        }

        return $taxPos;
    }

    /**
     * @param float|string $price
     * @param float|string $taxRate
     * @param int          $precision
     * @return float
     * @since since 5.0.0
     */
    public static function getGross($price, $taxRate, int $precision = 2): float
    {
        return \round($price * (100 + $taxRate) / 100, $precision);
    }

    /**
     * @param float|string $price
     * @param float|string $taxRate
     * @param int          $precision
     * @return float
     * @since since 5.0.0
     */
    public static function getNet($price, $taxRate, int $precision = 2): float
    {
        return \round($price / (100 + (float)$taxRate) * 100, $precision);
    }
}
