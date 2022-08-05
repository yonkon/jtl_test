<?php
/**
 * cLogin in tadmin should allow names longer then 20 characters
 *
 * @author fm
 * @created Tue, 12 Apr 2016 09:41:00 +0100
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20160412094100
 */
class Migration_20160412094100 extends Migration implements IMigration
{
    protected $author = 'fm';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('ALTER TABLE `tadminlogin` CHANGE COLUMN `cLogin` `cLogin` VARCHAR(255) NULL DEFAULT NULL;');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('ALTER TABLE `tadminlogin` CHANGE COLUMN `cLogin` `cLogin` VARCHAR(20) NULL DEFAULT NULL;');
    }
}
