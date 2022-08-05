<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210412123100
 */
class Migration_20210412123100 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add lang comparelist delete all';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'comparelist', 'comparelistDeleteAll', 'Alle Artikel löschen');
        $this->setLocalization('eng', 'comparelist', 'comparelistDeleteAll', 'Remove all items');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->removeLocalization('comparelistDeleteAll', 'comparelist');
    }
}
