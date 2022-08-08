<?php
declare(strict_types=1);

namespace Plugin\landswitcher\Migrations;

use JTL\Plugin\Migration;
use JTL\Update\IMigration;

/**
 * Class Migration20181126120000
 * @package Plugin\jtl_filterdemo\Migrations
 */
class Migration20220805180000 extends Migration implements IMigration
{
    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS `landswitcher_redirect_url` (
              `country_iso`  VARCHAR(5)      NOT NULL,
              `url` VARCHAR(255)      NOT NULL,
              PRIMARY KEY (`country_iso`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;'
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `landswitcher_redirect_url`');
    }
}
