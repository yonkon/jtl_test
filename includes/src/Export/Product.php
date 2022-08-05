<?php declare(strict_types=1);

namespace JTL\Export;

use JTL\Catalog\Category\Kategorie;
use JTL\Catalog\Currency;
use JTL\Catalog\Product\Artikel;
use JTL\Helpers\ShippingMethod;
use JTL\Helpers\Tax;
use JTL\Helpers\Text;
use stdClass;

/**
 * Class Product
 * @package JTL\Export
 */
class Product extends Artikel
{
    /**
     * @var string
     */
    public $cBeschreibungHTML;

    /**
     * @var string
     */
    public $cKurzBeschreibungHTML;

    /**
     * @var float
     */
    public $fUst;

    /**
     * @var string
     */
    public $Lieferbar;

    /**
     * @var int
     */
    public $Lieferbar_01;

    /**
     * @var int
     */
    public $kKundengruppe;

    /**
     * @var string
     */
    public $campaignValue;

    /**
     * @var int
     */
    public $kWaehrung;

    /**
     * @var float|int|string
     */
    public $Versandkosten;

    /**
     * @var float
     */
    public $currencyConversionFactor;

    /**
     * @var int
     */
    public $kSprache;

    /**
     * @var Kategorie
     */
    public $Kategorie;

    /**
     * @var string
     */
    public $Kategoriepfad;

    /**
     * @var string
     */
    public $cDeeplink;

    /**
     * @var string
     */
    public $Artikelbild;

    /**
     * @param array $config
     * @return $this
     */
    public function augmentProduct(array $config): self
    {
        $findTwo    = ["\r\n", "\r", "\n", "\x0B", "\x0"];
        $replaceTwo = [' ', ' ', ' ', ' ', ''];

        if (isset($config['exportformate_quot']) && $config['exportformate_quot'] !== 'N') {
            $findTwo[] = '"';
            if ($config['exportformate_quot'] === 'q' || $config['exportformate_quot'] === 'bq') {
                $replaceTwo[] = '\"';
            } elseif ($config['exportformate_quot'] === 'qq') {
                $replaceTwo[] = '""';
            } else {
                $replaceTwo[] = $config['exportformate_quot'];
            }
        }
        if (isset($config['exportformate_quot']) && $config['exportformate_equot'] !== 'N') {
            $findTwo[] = "'";
            if ($config['exportformate_equot'] === 'q' || $config['exportformate_equot'] === 'bq') {
                $replaceTwo[] = '"';
            } else {
                $replaceTwo[] = $config['exportformate_equot'];
            }
        }
        if (isset($config['exportformate_semikolon']) && $config['exportformate_semikolon'] !== 'N') {
            $findTwo[]    = ';';
            $replaceTwo[] = $config['exportformate_semikolon'];
        }

        $find                        = ['<br />', '<br>', '</'];
        $replace                     = [' ', ' ', ' </'];
        $this->cBeschreibungHTML     = Text::removeWhitespace(
            \str_replace(
                $findTwo,
                $replaceTwo,
                \str_replace('"', '&quot;', $this->cBeschreibung)
            )
        );
        $this->cKurzBeschreibungHTML = Text::removeWhitespace(
            \str_replace(
                $findTwo,
                $replaceTwo,
                \str_replace('"', '&quot;', $this->cKurzBeschreibung)
            )
        );
        $this->cName                 = Text::removeWhitespace(
            \str_replace(
                $findTwo,
                $replaceTwo,
                Text::unhtmlentities(\strip_tags(\str_replace($find, $replace, $this->cName)))
            )
        );
        $this->cBeschreibung         = Text::removeWhitespace(
            \str_replace(
                $findTwo,
                $replaceTwo,
                Text::unhtmlentities(\strip_tags(\str_replace($find, $replace, $this->cBeschreibung)))
            )
        );
        $this->cKurzBeschreibung     = Text::removeWhitespace(
            \str_replace(
                $findTwo,
                $replaceTwo,
                Text::unhtmlentities(
                    \strip_tags(\str_replace($find, $replace, $this->cKurzBeschreibung))
                )
            )
        );
        $this->fUst                  = Tax::getSalesTax($this->kSteuerklasse);
        $this->Preise->fVKBrutto     = Tax::getGross(
            $this->Preise->fVKNetto * $this->currencyConversionFactor,
            $this->fUst
        );
        $this->Preise->fVKNetto      = \round($this->Preise->fVKNetto, 2);
        $this->Versandkosten         = ShippingMethod::getLowestShippingFees(
            $config['exportformate_lieferland'] ?? '',
            $this,
            0,
            $this->kKundengruppe
        );
        if ($this->Versandkosten !== -1) {
            $price = Currency::convertCurrency($this->Versandkosten, null, $this->kWaehrung);
            if ($price !== false) {
                $this->Versandkosten = $price;
            }
        }
        // Kampagne URL
        if (!empty($this->campaignParameter)) {
            $cSep        = (\mb_strpos($this->cURL, '.php') !== false) ? '&' : '?';
            $this->cURL .= $cSep . $this->campaignParameter . '=' . $this->campaignValue;
        }
        $this->Lieferbar    = $this->fLagerbestand <= 0 ? 'N' : 'Y';
        $this->Lieferbar_01 = $this->fLagerbestand <= 0 ? 0 : 1;

        return $this;
    }

    /**
     * @param bool $fallback
     */
    public function addCategoryData(bool $fallback = false): void
    {
        $productCategoryID = $this->gibKategorie();
        if ($fallback === true) {
            // since 4.05 the product class only stores category IDs in Artikel::oKategorie_arr
            // but old google base exports rely on category attributes that wouldn't be available anymore
            // so in that case we replace oKategorie_arr with an array of real Kategorie objects
            $categories = [];
            foreach ($this->oKategorie_arr as $categoryID) {
                $categories[] = new Kategorie(
                    (int)$categoryID,
                    $this->kSprache,
                    $this->kKundengruppe
                );
            }
            $this->oKategorie_arr = $categories;
        }
        $this->Kategorie = new Kategorie(
            $productCategoryID,
            $this->kSprache,
            $this->kKundengruppe
        );
    }
}
