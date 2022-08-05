<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210212114600
 */
class Migration_20210212114600 extends Migration implements IMigration
{
    protected $author      = 'fm';
    protected $description = 'Add system page flag';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('ALTER TABLE `tlink` ADD COLUMN `bIsSystem` TINYINT(1) NOT NULL DEFAULT 0');
        $this->execute('ALTER TABLE `tlinkgruppe` ADD COLUMN `bIsSystem` TINYINT(1) NOT NULL DEFAULT 0');
        $this->execute('UPDATE `tlink` SET bIsSystem = 1 WHERE nLinkart IN (SELECT nLinkart FROM tspezialseite)');
        $this->execute('UPDATE `tlinkgruppe` SET bIsSystem = 1 
            WHERE cTemplatename IN (\'Kopf\', \'hidden\', \'Fuss\', \'megamenu\')');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('ALTER TABLE `tlink` DROP COLUMN `bIsSystem`');
    }
}
