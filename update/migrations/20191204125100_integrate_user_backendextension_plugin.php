<?php

/**
 * Integrate user backendextension plugin in shop core
 *
 * @author mh
 * @created Wed, 04 Dec 2019 12:51:00 +0200
 */

use JTL\Plugin\Admin\StateChanger;
use JTL\Plugin\Admin\Validation\LegacyPluginValidator;
use JTL\Plugin\Admin\Validation\PluginValidator;
use JTL\Update\IMigration;
use JTL\Update\Migration;
use JTL\XMLParser;

/**
 * Class Migration_20191204125100
 */
class Migration_20191204125100 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Integrate user backendextension plugin in shop core';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $db              = $this->getDB();
        $cache           = Shop::Container()->getCache();
        $parser          = new XMLParser();
        $legacyValidator = new LegacyPluginValidator($db, $parser);
        $pluginValidator = new PluginValidator($db, $parser);
        $stateChanger    = new StateChanger($db, $cache, $legacyValidator, $pluginValidator);

        $res = $db->getSingleObject(
            "SELECT kPlugin
                  FROM tplugin
                  WHERE cPluginID = 'jtl_backenduser_extension'"
        );
        if ($res !== null) {
            $stateChanger->deactivate((int)$res->kPlugin);
        }

        $this->execute(
          "UPDATE `tadminloginattribut`
               SET cAttribValue = 'N'
               WHERE cName = 'useAvatar'
               AND cAttribValue = 'G'"
        );
        $this->execute("DELETE FROM `tadminloginattribut` WHERE cName = 'useGPlus' OR cName = 'useGravatarEmail'");
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
    }
}
