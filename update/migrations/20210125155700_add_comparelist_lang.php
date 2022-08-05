<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210125155700
 */
class Migration_20210125155700 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add comparelist lang';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'comparelist', 'showLabels', 'Labels anzeigen');
        $this->setLocalization('ger', 'comparelist', 'hideLabels', 'Labels verstecken');
        $this->setLocalization('eng', 'comparelist', 'showLabels', 'Show labels');
        $this->setLocalization('eng', 'comparelist', 'hideLabels', 'Hide labels');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->removeLocalization('showLabels', 'comparelist');
        $this->removeLocalization('hideLabels', 'comparelist');
    }
}
