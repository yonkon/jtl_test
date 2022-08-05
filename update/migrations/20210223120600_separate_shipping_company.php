<?php

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210223120600
 */
class Migration_20210223120600 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Separate shipping company';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->setConfig(
            'lieferadresse_abfragen_firma',
            'N',
            CONF_KUNDEN,
            'Firma abfragen',
            'selectbox',
            325,
            (object)[
                'cBeschreibung' => 'Firma in Lieferadresse abfragen?',
                'inputOptions'  => [
                    'O' => 'Optional',
                    'Y' => 'Ja',
                    'N' => 'Nein',
                ],
            ]
        );
        $this->setConfig(
            'lieferadresse_abfragen_firmazusatz',
            'N',
            CONF_KUNDEN,
            'Firmenzusatz abfragen',
            'selectbox',
            327,
            (object)[
                'cBeschreibung' => 'Firmenzusatz in Lieferadresse abfragen?',
                'inputOptions'  => [
                    'O' => 'Optional',
                    'Y' => 'Ja',
                    'N' => 'Nein',
                ],
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->removeConfig('lieferadresse_abfragen_firma');
        $this->removeConfig('lieferadresse_abfragen_firmazusatz');
    }
}
