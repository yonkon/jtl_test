<?php declare(strict_types=1);

namespace JTL\OPC;

use Exception;
use JTL\Backend\AdminIO;
use JTL\Filter\Config;
use JTL\Filter\FilterInterface;
use JTL\Filter\Items\Category;
use JTL\Filter\Items\Characteristic;
use JTL\Filter\Items\Manufacturer;
use JTL\Filter\Items\PriceRange;
use JTL\Filter\Items\Rating;
use JTL\Filter\Items\Search;
use JTL\Filter\Items\SearchSpecial;
use JTL\Filter\Option;
use JTL\Filter\ProductFilter;
use JTL\Filter\Type;
use JTL\Helpers\Request;
use JTL\Helpers\Tax;
use JTL\OPC\Portlets\MissingPortlet\MissingPortlet;
use JTL\Shop;

/**
 * Class Service
 * @package JTL\OPC
 */
class Service
{
    /**
     * @var string
     */
    protected $adminName = '';

    /**
     * @var DB
     */
    protected $db;

    /**
     * @var null|Page
     */
    protected $curPage;

    /**
     * Service constructor.
     * @param DB $db
     * @throws Exception
     */
    public function __construct(DB $db)
    {
        $this->db = $db;
        Shop::Container()->getGetText()
            ->setLanguage(Shop::getCurAdminLangTag())
            ->loadAdminLocale('pages/opc');
    }

    /**
     * @return array list of the OPC service methods to be exposed for AJAX requests
     */
    public function getIOFunctionNames(): array
    {
        return [
            'getIOFunctionNames',
            'getBlueprints',
            'getBlueprint',
            'getBlueprintInstance',
            'getBlueprintPreview',
            'saveBlueprint',
            'deleteBlueprint',
            'getPortletInstance',
            'getPortletPreviewHtml',
            'getConfigPanelHtml',
            'getFilteredProductIds',
            'getFilterOptions',
            'getFilterList',
        ];
    }

    /**
     * @return string[]
     */
    public function getEditorMessages(): array
    {
        $messages     = [];
        $messageNames = [
            'opcImportSuccessTitle',
            'opcImportSuccess',
            'opcImportUnmappedS',
            'opcImportUnmappedP',
            'btnTitleCopyArea',
            'offscreenAreasDivider',
            'yesDeleteArea',
            'Cancel',
            'opcPageLocked',
            'dbUpdateNeeded',
            'indefinitePeriodOfTime',
            'notScheduled',
            'now',
        ];

        foreach ([13, 14, 7] as $i => $stepcount) {
            for ($j = 0; $j < $stepcount; $j++) {
                $messageNames[] = 'tutStepTitle_' . $i . '_' . $j;
                $messageNames[] = 'tutStepText_' . $i . '_' . $j;
            }
        }

        foreach ($messageNames as $name) {
            $messages[$name] = \__($name);
        }

        return $messages;
    }

    /**
     * @param AdminIO $io
     * @throws Exception
     */
    public function registerAdminIOFunctions(AdminIO $io): void
    {
        $adminAccount = $io->getAccount();

        if ($adminAccount === null) {
            throw new Exception('Admin account was not set on AdminIO.');
        }

        $this->adminName = $adminAccount->account()->cLogin;

        foreach ($this->getIOFunctionNames() as $functionName) {
            $publicFunctionName = 'opc' . \ucfirst($functionName);
            $io->register($publicFunctionName, [$this, $functionName], null, 'OPC_VIEW');
        }
    }

    /**
     * @return null|string
     * @throws Exception
     */
    public function getAdminSessionToken(): ?string
    {
        return Shop::getAdminSessionToken();
    }

    /**
     * @param bool $withInactive
     * @return PortletGroup[]
     * @throws Exception
     */
    public function getPortletGroups(bool $withInactive = false): array
    {
        return $this->db->getPortletGroups($withInactive);
    }

    /**
     * @param bool $withInactive
     * @return Portlet[]
     * @throws Exception
     */
    public function getAllPortlets(bool $withInactive = false): array
    {
        return $this->db->getAllPortlets($withInactive);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPortletInitScriptUrls(): array
    {
        $scripts = [];
        foreach ($this->getAllPortlets() as $portlet) {
            foreach ($portlet->getEditorInitScripts() as $script) {
                $path = $portlet->getBasePath() . $script;
                $url  = $portlet->getBaseUrl() . $script;
                if (!\array_key_exists($url, $scripts) && \file_exists($path)) {
                    $scripts[$url] = $url;
                }
            }
        }

        return $scripts;
    }

    /**
     * @param bool $withInactive
     * @return Blueprint[]
     * @throws Exception
     */
    public function getBlueprints(bool $withInactive = false): array
    {
        $blueprints = [];
        foreach ($this->db->getAllBlueprintIds($withInactive) as $blueprintId) {
            $blueprints[] = $this->getBlueprint($blueprintId);
        }

        return $blueprints;
    }

    /**
     * @param int $id
     * @return Blueprint
     * @throws Exception
     */
    public function getBlueprint(int $id): Blueprint
    {
        $blueprint = (new Blueprint())->setId($id);
        $this->db->loadBlueprint($blueprint);

        return $blueprint;
    }

    /**
     * @param int $id
     * @return PortletInstance
     * @throws Exception
     */
    public function getBlueprintInstance(int $id): PortletInstance
    {
        $instance = $this->getBlueprint($id)->getInstance();

        Shop::fire('shop.OPC.Service.getBlueprintInstance', [
            'id' => $id,
            'instance' => &$instance
        ]);

        return $instance;
    }

    /**
     * @param int $id
     * @return string
     * @throws Exception
     */
    public function getBlueprintPreview(int $id): string
    {
        return $this->getBlueprintInstance($id)->getPreviewHtml();
    }

    /**
     * @param string $name
     * @param array  $data
     * @throws Exception
     */
    public function saveBlueprint(string $name, array $data): void
    {
        $blueprint = (new Blueprint())->deserialize(['name' => $name, 'content' => $data]);
        $this->db->saveBlueprint($blueprint);
    }

    /**
     * @param int $id
     */
    public function deleteBlueprint(int $id): void
    {
        $blueprint = (new Blueprint())->setId($id);
        $this->db->deleteBlueprint($blueprint);
    }

    /**
     * @param string $class
     * @return PortletInstance
     * @throws Exception
     */
    public function createPortletInstance(string $class): PortletInstance
    {
        $portlet = $this->db->getPortlet($class);

        if ($portlet instanceof MissingPortlet) {
            return new MissingPortletInstance($portlet, $portlet->getMissingClass());
        }

        return new PortletInstance($portlet);
    }

    /**
     * @param array $data
     * @return PortletInstance
     * @throws Exception
     */
    public function getPortletInstance(array $data): PortletInstance
    {
        if ($data['class'] === 'MissingPortlet') {
            return $this->createPortletInstance($data['missingClass'])
                ->deserialize($data);
        }

        return $this->createPortletInstance($data['class'])
            ->deserialize($data);
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function getPortletPreviewHtml(array $data): string
    {
        return $this->getPortletInstance($data)->getPreviewHtml();
    }

    /**
     * @param string      $portletClass
     * @param string|null $missingClass
     * @param array       $props
     * @return string
     * @throws Exception
     */
    public function getConfigPanelHtml(string $portletClass, ?string $missingClass, array $props): string
    {
        return $this->getPortletInstance([
            'class'        => $portletClass,
            'missingClass' => $missingClass,
            'properties'   => $props,
        ])->getConfigPanelHtml();
    }

    /**
     * @return bool
     */
    public function isEditMode(): bool
    {
        return Request::verifyGPDataString('opcEditMode') === 'yes';
    }

    /**
     * @return bool
     */
    public function isOPCInstalled(): bool
    {
        return $this->db->isOPCInstalled();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function shopHasUpdates(): bool
    {
        return $this->db->shopHasUpdates();
    }

    /**
     * @return bool
     */
    public function isPreviewMode(): bool
    {
        return Request::verifyGPDataString('opcPreviewMode') === 'yes';
    }

    /**
     * @return int
     */
    public function getEditedPageKey(): int
    {
        return Request::verifyGPCDataInt('opcEditedPageKey');
    }

    /**
     * @param string $propname
     * @param array  $enabledFilters
     * @return string
     * @throws \SmartyException
     */
    public function getFilterList(string $propname, array $enabledFilters = []): string
    {
        $filters = $this->getFilterOptions($enabledFilters);

        return Shop::Smarty()->assign('propname', $propname)
            ->assign('filters', $filters)
            ->fetch(\PFAD_ROOT . \PFAD_ADMIN . 'opc/tpl/config/filter-list.tpl');
    }

    /**
     * @param string        $filterClass
     * @param array         $params
     * @param mixed         $value
     * @param ProductFilter $pf
     * @return void
     */
    public function getFilterClassParamMapping(string $filterClass, array &$params, $value, ProductFilter $pf): void
    {
        switch ($filterClass) {
            case Category::class:
                $params['kKategorie'] = $value;
                break;
            case Characteristic::class:
                $params['MerkmalFilter_arr'][] = $value;
                break;
            case PriceRange::class:
                $params['cPreisspannenFilter'] = $value;
                break;
            case Manufacturer::class:
                $params['kHersteller'] = $value;
                break;
            case Rating::class:
                $params['nBewertungSterneFilter'] = $value;
                break;
            case SearchSpecial::class:
                $params['kSuchspecialFilter'] = $value;
                break;
            case Search::class:
                $params['kSuchFilter']      = $value;
                $params['SuchFilter'][]     = $value;
                $params['SuchFilter_arr'][] = $value;
                break;
            default:
                /** @var FilterInterface $instance */
                $instance                       = new $filterClass($pf);
                $_GET[$instance->getUrlParam()] = $value;
                break;
        }
    }

    /**
     * @param ProductFilter $pf
     */
    public function overrideConfig(ProductFilter $pf): void
    {
        $config = $pf->getFilterConfig()->getConfig();
        if (isset($config['navigationsfilter'])) {
            $config['navigationsfilter']['allgemein_kategoriefilter_benutzen']    = 'Y';
            $config['navigationsfilter']['allgemein_availabilityfilter_benutzen'] = 'Y';
            $config['navigationsfilter']['allgemein_herstellerfilter_benutzen']   = 'Y';
            $config['navigationsfilter']['bewertungsfilter_benutzen']             = 'Y';
            $config['navigationsfilter']['preisspannenfilter_benutzen']           = 'Y';
            $config['navigationsfilter']['merkmalfilter_verwenden']               = 'Y';
            $config['navigationsfilter']['kategoriefilter_anzeigen_als']          = 'KA';
            $pf->getFilterConfig()->setConfig($config);
        }
    }

    /**
     * @param array $enabledFilters
     * @return array
     */
    public function getFilterOptions(array $enabledFilters = []): array
    {
        Tax::setTaxRates();
        $pf         = new ProductFilter(
            Config::getDefault(),
            Shop::Container()->getDB(),
            Shop::Container()->getCache()
        );
        $results    = [];
        $enabledMap = [];
        $params     = ['MerkmalFilter_arr' => [], 'SuchFilter_arr' => [], 'SuchFilter' => []];
        foreach ($enabledFilters as $enabledFilter) {
            $this->getFilterClassParamMapping($enabledFilter['class'], $params, $enabledFilter['value'], $pf);
            $enabledMap[$enabledFilter['class'] . ':' . $enabledFilter['value']] = true;
        }
        $this->overrideConfig($pf);
        $pf->initStates($params);
        foreach ($pf->getAvailableFilters() as $availableFilter) {
            $availableFilter->setType(Type::AND);
            $class   = $availableFilter->getClassName();
            $name    = $availableFilter->getFrontendName();
            $options = [];
            if ($class === Characteristic::class) {
                foreach ($availableFilter->getOptions() as $option) {
                    foreach ($option->getOptions() as $suboption) {
                        /** @var Option $suboption */
                        $value    = $suboption->kMerkmalWert;
                        $mapindex = $class . ':' . $value;

                        if (!isset($enabledMap[$mapindex])) {
                            $options[] = [
                                'name'  => $suboption->getName(),
                                'value' => $value,
                                'count' => $suboption->getCount(),
                                'class' => $class,
                            ];
                        }
                    }
                }
            } else {
                foreach ($availableFilter->getOptions() as $option) {
                    $value    = $option->getValue();
                    $mapindex = $class . ':' . $value;

                    if (!isset($enabledMap[$mapindex])) {
                        $options[] = [
                            'name'  => $option->getName(),
                            'value' => $value,
                            'count' => $option->getCount(),
                            'class' => $class,
                        ];
                    }
                }
            }

            if (\count($options) > 0) {
                $results[] = [
                    'name'    => $name,
                    'class'   => $class,
                    'options' => $options,
                ];
            }
        }

        return $results;
    }
}
