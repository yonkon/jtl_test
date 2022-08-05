<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210217124900
 */
class Migration_20210217124900 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add show comparelist setting';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setConfig(
            'vergleichsliste_anzeigen',
            'Y',
            CONF_VERGLEICHSLISTE,
            'Vergleichliste nutzen',
            'selectbox',
            105,
            (object)[
                'cBeschreibung' => 'Vergleichliste nutzen?',
                'inputOptions'  => [
                    'Y' => 'Ja',
                    'N' => 'Nein'
                ],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->removeConfig('vergleichsliste_anzeigen');
    }
}
