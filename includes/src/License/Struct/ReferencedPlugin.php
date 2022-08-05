<?php declare(strict_types=1);

namespace JTL\License\Struct;

use Carbon\Carbon;
use Exception;
use JTL\DB\DbInterface;
use JTL\Plugin\State;
use JTLShop\SemVer\Version;
use stdClass;

/**
 * Class ReferencedPlugin
 * @package JTL\License\Struct
 */
class ReferencedPlugin extends ReferencedItem
{
    /**
     * @inheritDoc
     */
    public function initByExsID(DbInterface $db, stdClass $license, Releases $releases): void
    {
        $installed = $db->select('tplugin', 'exsID', $license->exsid);
        if ($installed !== null) {
            $available        = $releases->getAvailable();
            $latest           = $releases->getLatest();
            $installedVersion = Version::parse($installed->nVersion);
            $availableVersion = $available === null ? Version::parse('0.0.0') : $available->getVersion();
            $latestVersion    = $latest === null ? $availableVersion : $latest->getVersion();
            $this->setHasUpdate(false);
            $this->setCanBeUpdated(false);
            $this->setID($installed->cPluginID);
            $this->setMaxInstallableVersion($installedVersion);
            if ($availableVersion->greaterThan($installedVersion)) {
                $this->setMaxInstallableVersion($availableVersion);
                $this->setHasUpdate(true);
                $this->setCanBeUpdated(true);
            } elseif ($latestVersion->greaterThan($availableVersion)
                && $latestVersion->greaterThan($installedVersion)
            ) {
                $this->setMaxInstallableVersion($latestVersion);
                $this->setHasUpdate(true);
                $this->setCanBeUpdated(false);
            }
            $this->setInstalled(true);
            $this->setInstalledVersion($installedVersion);
            $this->setActive((int)$installed->nStatus === State::ACTIVATED);
            $this->setInternalID((int)$installed->kPlugin);
            try {
                $carbon        = new Carbon($installed->dInstalliert);
                $dateInstalled = $carbon->toIso8601ZuluString();
            } catch (Exception $e) {
                $dateInstalled = null;
            }
            $this->setDateInstalled($dateInstalled);
            $this->setInitialized(true);
        }
    }
}
