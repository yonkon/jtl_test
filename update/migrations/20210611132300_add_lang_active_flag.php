<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210611132300
 */
class Migration_20210611132300 extends Migration implements IMigration
{
    protected $author = 'dr';
    protected $description = 'Add tsprache.active flag';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->execute("ALTER TABLE tsprache ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->execute("ALTER TABLE tsprache DROP COLUMN active");
    }
}
