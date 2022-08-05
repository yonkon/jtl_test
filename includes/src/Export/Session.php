<?php declare(strict_types=1);

namespace JTL\Export;

use JTL\Catalog\Currency;
use JTL\Customer\CustomerGroup;
use JTL\DB\DbInterface;
use JTL\Helpers\Tax;
use JTL\Language\LanguageModel;
use JTL\Session\Frontend;
use JTL\Shop;
use stdClass;
use function Functional\first;

/**
 * Class Session
 * @package JTL\Export
 */
class Session
{
    /**
     * @var stdClass
     */
    private $oldSession;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @param Currency $currency
     * @return Session
     */
    public function setCurrency(Currency $currency): Session
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @param Model       $model
     * @param DbInterface $db
     * @return $this
     */
    public function initSession(Model $model, DbInterface $db): self
    {
        if (isset($_SESSION['Kundengruppe'])) {
            $this->oldSession               = new stdClass();
            $this->oldSession->Kundengruppe = $_SESSION['Kundengruppe'];
            $this->oldSession->kSprache     = $_SESSION['kSprache'];
            $this->oldSession->cISO         = $_SESSION['cISOSprache'];
            $this->oldSession->Waehrung     = Frontend::getCurrency();
        }
        $languageID     = $model->getLanguageID();
        $this->currency = $model->getCurrencyID() > 0
            ? new Currency($model->getCurrencyID())
            : (new Currency())->getDefault();
        Tax::setTaxRates();
        $net       = $db->select('tkundengruppe', 'kKundengruppe', $model->getCustomerGroupID());
        $languages = Shop::Lang()->gibInstallierteSprachen();
        $langISO   = first($languages, static function (LanguageModel $l) use ($languageID) {
            return $l->getId() === $languageID;
        });

        $_SESSION['Kundengruppe']  = (new CustomerGroup($model->getCustomerGroupID()))
            ->setMayViewPrices(1)
            ->setMayViewCategories(1)
            ->setIsMerchant((int)($net->nNettoPreise ?? 0));
        $_SESSION['kKundengruppe'] = $model->getCustomerGroupID();
        $_SESSION['kSprache']      = $languageID;
        $_SESSION['Sprachen']      = $languages;
        $_SESSION['Waehrung']      = $this->currency;
        Shop::setLanguage($languageID, $langISO->cISO ?? null);

        return $this;
    }

    /**
     * @return $this
     */
    public function restoreSession(): self
    {
        if ($this->oldSession !== null) {
            $_SESSION['Kundengruppe'] = $this->oldSession->Kundengruppe;
            $_SESSION['Waehrung']     = $this->oldSession->Waehrung;
            $_SESSION['kSprache']     = $this->oldSession->kSprache;
            $_SESSION['cISOSprache']  = $this->oldSession->cISO;
            Shop::setLanguage($this->oldSession->kSprache, $this->oldSession->cISO);
        }

        return $this;
    }
}
