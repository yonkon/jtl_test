<?php
/**
 * syntax checks
 *
 * @author fm
 * @created Mon, 16 May 2019 12:23:00 +0200
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20190506122300
 */
class Migration_20190506122300 extends Migration implements IMigration
{
    protected $author      = 'fm';
    protected $description = 'Link references';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('ALTER TABLE `tlink` ADD COLUMN `reference` INT(10) UNSIGNED NOT NULL DEFAULT 0');
        $references = $this->getDB()->getObjects(
            "SELECT kLink, cName
                FROM tlink
                WHERE cName RLIKE 'Referenz [0-9]+'
                  AND nLinkart = :linkartReference",
            ['linkartReference' => LINKTYP_REFERENZ]
        );
        foreach ($references as $reference) {
            if (preg_match('/Referenz ([\d]+)/', $reference->cName, $hits)) {
                $this->getDB()->update('tlink', 'kLink', $reference->kLink, (object)['reference' => (int)$hits[1]]);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('ALTER TABLE `tlink` DROP COLUMN `reference`');
    }
}
