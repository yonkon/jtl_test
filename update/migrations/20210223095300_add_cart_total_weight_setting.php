<?php

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210223095300
 */
class Migration_20210223095300 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add cart total weight setting';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->setConfig(
            'warenkorb_gesamtgewicht_anzeigen',
            'N',
            CONF_KAUFABWICKLUNG,
            'Gesamtgewicht alle Artikel auf Warenkorb-Seite anzeigen.',
            'selectbox',
            265,
            (object)[
                'cBeschreibung' => 'Gesamtgewicht alle Artikel auf Warenkorb-Seite anzeigen.',
                'inputOptions'  => [
                    'Y' => 'Ja',
                    'N' => 'Nein',
                ],
            ]
        );

        $this->setLocalization('ger', 'basket', 'cartTotalWeight', 'Das Gesamtgewicht aller Artikel im Warenkorb beträgt %s kg.');
        $this->setLocalization('eng', 'basket', 'cartTotalWeight', 'The total weight of all items in the basket is %s kg.');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->removeConfig('warenkorb_gesamtgewicht_anzeigen');

        $this->removeLocalization('cartTotalWeight', 'basket');
    }
}
