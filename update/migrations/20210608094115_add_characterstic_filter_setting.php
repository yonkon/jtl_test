<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210608094115
 */
class Migration_20210608094115 extends Migration implements IMigration
{
    protected $author      = 'fp';
    protected $description = 'Add characteristic filter setting';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->setConfig(
            'merkmalfilter_trefferanzahl_anzeigen',
            'E',
            \CONF_NAVIGATIONSFILTER,
            'Trefferanzahl bei Merkmalfiltern anzeigen',
            'selectbox',
            183,
            (object)[
                'cBeschreibung' => 'Trefferanzahl bei Merkmalfiltern anzeigen?',
                'inputOptions'  => [
                    'N' => 'Trefferanzahl nie anzeigen',
                    'E' => 'Trefferanzahl nur bei Einfachauswahl anzeigen',
                    'Y' => 'Trefferanzahl auch bei möglicher Mehrfachauswahl anzeigen (performancelastig)'
                ],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->removeConfig('merkmalfilter_trefferanzahl_anzeigen');
    }
}
