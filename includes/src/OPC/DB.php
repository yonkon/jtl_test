<?php declare(strict_types=1);

namespace JTL\OPC;

use Exception;
use InvalidArgumentException;
use JTL\Cache\JTLCacheInterface;
use JTL\DB\DbInterface;
use JTL\OPC\Portlets\MissingPortlet\MissingPortlet;
use JTL\Plugin\PluginLoader;
use JTL\Update\Updater;
use stdClass;
use function Functional\map;

/**
 * Class DB
 * @package JTL\OPC
 */
class DB
{
    /**
     * @var DbInterface
     */
    protected $shopDB;

    /**
     * @var JTLCacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $mapping;

    /**
     * DB constructor.
     * @param DbInterface       $shopDB
     * @param JTLCacheInterface $cache
     */
    public function __construct(DbInterface $shopDB, JTLCacheInterface $cache)
    {
        $this->shopDB  = $shopDB;
        $this->cache   = $cache;
        $this->mapping = $this->cache->get('jtl_opc_mapping');
        if ($this->mapping === false) {
            $this->mapping = [];
        }
    }

    /**
     * @param bool $withInactive
     * @return int[]
     */
    public function getAllBlueprintIds(bool $withInactive = false): array
    {
        return map($this->shopDB->selectAll(
            'topcblueprint',
            $withInactive ? [] : 'bActive',
            $withInactive ? [] : 1,
            'kBlueprint'
        ), static function ($e) {
            return (int)$e->kBlueprint;
        });
    }

    /**
     * @param Blueprint $blueprint
     * @return bool
     */
    public function blueprintExists(Blueprint $blueprint): bool
    {
        return \is_object($this->shopDB->select('topcblueprint', 'kBlueprint', $blueprint->getId()));
    }

    /**
     * @param Blueprint $blueprint
     * @return $this
     */
    public function deleteBlueprint(Blueprint $blueprint): self
    {
        $this->shopDB->delete('topcblueprint', 'kBlueprint', $blueprint->getId());

        return $this;
    }

    /**
     * @param Blueprint $blueprint
     * @throws Exception
     */
    public function loadBlueprint(Blueprint $blueprint): void
    {
        $blueprintDB = $this->shopDB->select('topcblueprint', 'kBlueprint', $blueprint->getId());

        if (!\is_object($blueprintDB)) {
            throw new Exception('The OPC blueprint with the id \'' . $blueprint->getId() . '\' could not be found.');
        }

        $content = \json_decode($blueprintDB->cJson, true);

        $blueprint->setId((int)$blueprintDB->kBlueprint)
            ->setName($blueprintDB->cName)
            ->deserialize(['name' => $blueprintDB->cName, 'content' => $content]);
    }

    /**
     * @param Blueprint $blueprint
     * @return $this
     * @throws Exception
     */
    public function saveBlueprint(Blueprint $blueprint): self
    {
        if ($blueprint->getName() === '') {
            throw new Exception('The OPC blueprint data to be saved is incomplete or invalid.');
        }

        $blueprintDB = (object)[
            'kBlueprint' => $blueprint->getId(),
            'cName'      => $blueprint->getName(),
            'cJson'      => \json_encode($blueprint->getInstance()),
        ];

        if ($this->blueprintExists($blueprint)) {
            $res = $this->shopDB->update('topcblueprint', 'kBlueprint', $blueprint->getId(), $blueprintDB);
            if ($res === -1) {
                throw new Exception('The OPC blueprint could not be updated in the DB.');
            }
        } else {
            $key = $this->shopDB->insert('topcblueprint', $blueprintDB);
            if ($key === 0) {
                throw new Exception('The OPC blueprint could not be inserted into the DB.');
            }

            $blueprint->setId($key);
        }

        return $this;
    }

    /**
     * @param bool $withInactive
     * @return array
     * @throws Exception
     */
    public function getPortletGroups(bool $withInactive = false): array
    {
        $groupNames = $this->shopDB->getObjects('SELECT DISTINCT(cGroup) FROM topcportlet ORDER BY cGroup ASC');
        $groups     = [];
        foreach ($groupNames as $groupName) {
            $groups[] = $this->getPortletGroup($groupName->cGroup, $withInactive);
        }

        return $groups;
    }

    /**
     * @param string $groupName
     * @param bool   $withInactive
     * @return PortletGroup
     * @throws Exception
     */
    public function getPortletGroup(string $groupName, bool $withInactive = false): PortletGroup
    {
        $portletsDB   = $this->shopDB->selectAll(
            'topcportlet',
            $withInactive ? 'cGroup' : ['cGroup', 'bActive'],
            $withInactive ? $groupName : [$groupName, 1],
            'cClass',
            'cTitle'
        );
        $portletGroup = new PortletGroup($groupName);
        foreach ($portletsDB as $portletDB) {
            $portletGroup->addPortlet($this->getPortlet($portletDB->cClass));
        }

        return $portletGroup;
    }

    /**
     * @param bool $withInactive
     * @return Portlet[]
     * @throws Exception
     */
    public function getAllPortlets(bool $withInactive = false): array
    {
        $portlets   = [];
        $portletsDB = $this->shopDB->selectAll(
            'topcportlet',
            $withInactive ? [] : 'bActive',
            $withInactive ? [] : 1,
            'cClass',
            'cTitle'
        );
        foreach ($portletsDB as $portletDB) {
            $portlets[] = $this->getPortlet($portletDB->cClass);
        }

        return $portlets;
    }

    /**
     * @return int
     */
    public function getPortletCount(): int
    {
        return (int)$this->shopDB->getSingleObject('SELECT COUNT(kPortlet) AS count FROM topcportlet')->count;
    }

    /**
     * @param string $class
     * @return stdClass|null
     */
    protected function getPortletByClassName(string $class): ?stdClass
    {
        if (isset($this->mapping[$class])) {
            return $this->mapping[$class];
        }
        $mapping = $this->shopDB->select('topcportlet', 'cClass', $class);
        if ($mapping !== null) {
            $mapping->kPortlet = (int)$mapping->kPortlet;
            $mapping->kPlugin  = (int)$mapping->kPlugin;
            $mapping->bActive  = (int)$mapping->bActive;
        }
        $this->mapping[$class] = $mapping;
        $this->cache->set('jtl_opc_mapping', $this->mapping, [\CACHING_GROUP_OPC]);

        return $mapping;
    }

    /**
     * @param string $class
     * @return Portlet
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function getPortlet(string $class): Portlet
    {
        if ($class === '') {
            throw new InvalidArgumentException('The OPC portlet class name "' . $class . '" is invalid.');
        }
        $plugin     = null;
        $pluginID   = 0;
        $data       = $this->getPortletByClassName($class);
        $installed  = $data !== null;
        $active     = $installed && $data->bActive === 1;
        $fromPlugin = $installed && $data->kPlugin > 0;
        $fullClass  = '\JTL\OPC\Portlets\\' . $class . '\\' . $class;
        if ($fromPlugin) {
            $pluginID = $data->kPlugin;
            if (\SAFE_MODE === true) {
                $active = 0;
            } else {
                $loader    = new PluginLoader($this->shopDB, $this->cache);
                $plugin    = $loader->init($pluginID);
                $fullClass = '\Plugin\\' . $plugin->getPluginID() . '\Portlets\\' . $class . '\\' . $class;
            }
        }

        if ($installed && $active) {
            $portlet = \class_exists($fullClass)
                ? new $fullClass($class, $data->kPortlet, $pluginID)
                : new Portlet($class, $data->kPortlet, $pluginID);

            return $portlet
                ->setTitle($data->cTitle)
                ->setGroup($data->cGroup)
                ->setActive($data->bActive === 1);
        }
        $portlet = new MissingPortlet('MissingPortlet', 0, 0);
        $portlet->setMissingClass($class)
            ->setTitle('Missing Portlet "' . $class . '"')
            ->setGroup('hidden')
            ->setActive(false);

        if ($fromPlugin) {
            $portlet->setInactivePlugin($plugin)
                ->setTitle('Missing Portlet "' . $class . '" (' . $pluginID . ')');
        }

        return $portlet;
    }

    /**
     * @return bool
     */
    public function isOPCInstalled(): bool
    {
        if (($installed = $this->cache->get('opc_installed')) === false) {
            $installed = $this->shopDB->select('tmigration', 'kMigration', 20180507101900) !== null;
            $this->cache->set('opc_installed', $installed);
        }

        return $installed;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function shopHasUpdates(): bool
    {
        return (new Updater($this->shopDB))->hasPendingUpdates();
    }
}
