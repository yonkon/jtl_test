<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210713150000
 */
class Migration_20210713150000 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add delete filter lang';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'global', 'deleteFilter', 'Diesen Filter entfernen');
        $this->setLocalization('eng', 'global', 'deleteFilter', 'Remove this filter');
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function down()
    {
        $this->removeLocalization('deleteFilter', 'global');
    }
}
