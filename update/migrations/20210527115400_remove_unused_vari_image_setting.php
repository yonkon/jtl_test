<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210527115400
 */
class Migration_20210527115400 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Remove unused vari image setting';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->removeConfig('artikeldetails_variationskombikind_bildvorschau');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->setConfig(
            'artikeldetails_variationskombikind_bildvorschau',
            'N',
            CONF_ARTIKELDETAILS,
            'Bildervorschau von Variationskombikinder anzeigen',
            'selectbox',
            499,
            (object)[
                'cBeschreibung' => 'Soll in der Artikel?bersicht die Vorschaubilder von Variationskombikinder (falls '
                        . 'vorhanden) angezeigt werden?',
                'inputOptions'  => [
                    'Y' => 'Ja',
                    'N' => 'Nein'
                ]
            ]
        );
    }
}
