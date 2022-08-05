<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210609152400
 */
class Migration_20210609152400 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Fix eng youtube consent name';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->execute("UPDATE `tconsentlocalization` SET `name` = 'YouTube' WHERE `name`  = 'YoutTube'");
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
    }
}
