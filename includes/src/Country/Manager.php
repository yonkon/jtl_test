<?php declare(strict_types=1);

namespace JTL\Country;

use JTL\Alert\Alert;
use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\L10n\GetText;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Services\JTL\AlertServiceInterface;
use JTL\Services\JTL\CountryService;
use JTL\Services\JTL\CountryServiceInterface;
use JTL\Smarty\JTLSmarty;

/**
 * Class Manager
 * @package JTL\Country
 */
class Manager
{
    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * @var JTLSmarty
     */
    protected $smarty;

    /**
     * @var CountryServiceInterface
     */
    protected $countryService;

    /**
     * @var JTLCacheInterface
     */
    protected $cache;

    /**
     * @var AlertServiceInterface
     */
    protected $alertService;

    /**
     * @var GetText
     */
    protected $getText;

    /**
     * Manager constructor.
     * @param DbInterface $db
     * @param JTLSmarty $smarty
     * @param CountryServiceInterface $countryService
     * @param JTLCacheInterface $cache
     * @param AlertServiceInterface $alertService
     * @param GetText $getText
     */
    public function __construct(
        DbInterface $db,
        JTLSmarty $smarty,
        CountryServiceInterface $countryService,
        JTLCacheInterface $cache,
        AlertServiceInterface $alertService,
        GetText $getText
    ) {
        $this->db             = $db;
        $this->smarty         = $smarty;
        $this->countryService = $countryService;
        $this->cache          = $cache;
        $this->alertService   = $alertService;
        $this->getText        = $getText;

        $getText->loadAdminLocale('pages/countrymanager');
    }

    /**
     * @param string $step
     * @throws \SmartyException
     */
    public function finalize(string $step): void
    {
        switch ($step) {
            case 'add':
                $this->smarty->assign('countryPost', Text::filterXSS($_POST));
                break;
            case 'update':
                $country = $this->countryService->getCountry(Request::verifyGPDataString('cISO'));
                if ($country->isShippingAvailable()) {
                    $this->alertService->addAlert(
                        Alert::TYPE_WARNING,
                        \__('warningShippingAvailable'),
                        'warningShippingAvailable'
                    );
                }
                $this->smarty
                    ->assign('countryPost', Text::filterXSS($_POST))
                    ->assign('country', $country);
                break;
            default:
                break;
        }

        $this->smarty->assign('step', $step)
            ->assign('countries', $this->countryService->getCountrylist())
            ->assign('continents', $this->countryService->getContinents())
            ->display('countrymanager.tpl');
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        $action = 'overview';
        if (Request::verifyGPDataString('action') !== '' && Form::validateToken()) {
            $action = Request::verifyGPDataString('action');
        }
        switch ($action) {
            case 'add':
                $action = $this->addCountry(Text::filterXSS($_POST));
                $this->alertService->addAlert(
                    Alert::TYPE_WARNING,
                    \__('warningCreateCountryInWawi'),
                    'warningCreateCountryInWawi'
                );
                break;
            case 'delete':
                $action = $this->deleteCountry();
                break;
            case 'update':
                $action = $this->updateCountry(Text::filterXSS($_POST));
                break;
            default:
                break;
        }

        return $action;
    }

    /**
     * @param array $postData
     * @return string
     */
    private function addCountry(array $postData): string
    {
        $iso = \mb_strtoupper($postData['cISO'] ?? '');
        if ($this->countryService->getCountry($iso) !== null) {
            $this->alertService->addAlert(
                Alert::TYPE_DANGER,
                \sprintf(\__('errorCountryIsoExists'), $iso),
                'errorCountryIsoExists'
            );
            return 'add';
        }
        if ($iso === '' || Request::postInt('save') !== 1 || !$this->checkIso($iso)) {
            return 'add';
        }
        $country                          = new \stdClass();
        $country->cISO                    = $iso;
        $country->cDeutsch                = $postData['cDeutsch'];
        $country->cEnglisch               = $postData['cEnglisch'];
        $country->nEU                     = $postData['nEU'];
        $country->cKontinent              = $postData['cKontinent'];
        $country->bPermitRegistration     = $postData['bPermitRegistration'];
        $country->bRequireStateDefinition = $postData['bRequireStateDefinition'];

        $this->db->insert('tland', $country);
        $this->cache->flush(CountryService::CACHE_ID);
        $this->alertService->addAlert(
            Alert::TYPE_SUCCESS,
            \sprintf(\__('successCountryAdd'), $iso),
            'successCountryAdd',
            ['saveInSession' => true]
        );

        $this->refreshPage();

        return 'add';
    }

    /**
     * @return string
     */
    private function deleteCountry(): string
    {
        $iso = Text::filterXSS(Request::verifyGPDataString('cISO'));
        if ($this->db->delete('tland', 'cISO', $iso) > 0) {
            $this->cache->flush(CountryService::CACHE_ID);
            $this->alertService->addAlert(
                Alert::TYPE_SUCCESS,
                \sprintf(\__('successCountryDelete'), $iso),
                'successCountryDelete',
                ['saveInSession' => true]
            );

            $this->refreshPage();
        }

        return 'delete';
    }

    /**
     * @param array $postData
     * @return string
     */
    private function updateCountry(array $postData): string
    {
        if (Request::postInt('save') !== 1 || !$this->checkIso($postData['cISO'])) {
            return 'update';
        }
        $country                          = new \stdClass();
        $country->cDeutsch                = $postData['cDeutsch'];
        $country->cEnglisch               = $postData['cEnglisch'];
        $country->nEU                     = $postData['nEU'];
        $country->cKontinent              = $postData['cKontinent'];
        $country->bPermitRegistration     = $postData['bPermitRegistration'];
        $country->bRequireStateDefinition = $postData['bRequireStateDefinition'];

        $this->db->update(
            'tland',
            'cISO',
            $postData['cISO'],
            $country
        );
        $this->cache->flush(CountryService::CACHE_ID);
        $this->alertService->addAlert(
            Alert::TYPE_SUCCESS,
            \sprintf(\__('successCountryUpdate'), $postData['cISO']),
            'successCountryUpdate',
            ['saveInSession' => true]
        );

        $this->refreshPage();

        return 'update';
    }

    /**
     * @param string $iso
     * @return bool
     */
    private function checkIso(string $iso): bool
    {
        $countryName = \locale_get_display_region('sl-Latn-' . $iso . '-nedis', 'en');
        if ($countryName === '' || $countryName === $iso) {
            $this->alertService->addAlert(
                Alert::TYPE_ERROR,
                \sprintf(\__('errorIsoDoesNotExist'), $iso),
                'errorIsoDoesNotExist'
            );

            return false;
        }

        return true;
    }

    /**
     * refresh for CountryService
     */
    private function refreshPage(): void
    {
        \header('Refresh:0');
        exit;
    }


    /**
     * @param array $inactiveCountries
     * @param bool $showAlerts
     */
    public function updateRegistrationCountries(array $inactiveCountries = [], bool $showAlerts = true): void
    {
        $deactivated      = [];
        $currentCountries = $this->db->getCollection('SELECT cISO FROM tland WHERE bPermitRegistration=1')
            ->pluck('cISO')->toArray();
        $this->db->query(
            "UPDATE tland
                INNER JOIN tversandart
                  ON tversandart.cLaender RLIKE CONCAT(tland.cISO, ' ')
                SET tland.bPermitRegistration = 1
                WHERE tland.bPermitRegistration = 0"
        );
        $newCountries = $this->db->getCollection('SELECT cISO FROM tland WHERE bPermitRegistration=1')
            ->pluck('cISO')->toArray();
        $activated    = \array_diff($newCountries, $currentCountries);

        if (\count($inactiveCountries) > 0) {
            $possibleShippingCountries = $this->db->getCollection(
                "SELECT DISTINCT(tland.cISO)
                  FROM tland
                  INNER JOIN tversandart
                    ON tversandart.cLaender RLIKE CONCAT(tland.cISO, ' ')"
            )->pluck('cISO')->toArray();
            $deactivated               = \array_diff($inactiveCountries, $possibleShippingCountries);
            $this->db->query(
                "UPDATE tland
                    SET bPermitRegistration = 0
                    WHERE cISO IN ('" . \implode("', '", Text::filterXSS($deactivated)) . "')"
            );
        }


        if ($showAlerts) {
            if (\count($activated) > 0) {
                $activatedCountries = $this->countryService->getFilteredCountryList($activated)->map(
                    static function (Country $country) {
                        return $country->getName();
                    }
                )->toArray();
                $this->alertService->addAlert(
                    Alert::TYPE_INFO,
                    \sprintf(\__('infoRegistrationCountriesActivated'), \implode(', ', $activatedCountries)),
                    'infoRegistrationCountriesActivated'
                );
            }
            if (\count($deactivated) > 0) {
                $deactivatedCountries = $this->countryService->getFilteredCountryList($deactivated)->map(
                    static function (Country $country) {
                        return $country->getName();
                    }
                )->toArray();
                $this->alertService->addAlert(
                    Alert::TYPE_WARNING,
                    \sprintf(\__('warningRegistrationCountriesDeactivated'), \implode(', ', $deactivatedCountries)),
                    'warningRegistrationCountriesDeactivated'
                );
            }
        }
    }
}
