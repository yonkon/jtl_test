<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210630091300
 */
class Migration_20210630091300 extends Migration implements IMigration
{
    protected $author = 'mh';
    protected $description = 'Ust setting no percent';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->execute(
            "INSERT INTO `teinstellungenconfwerte` (`kEinstellungenConf`,`cName`,`cWert`,`nSort`)
                VALUES (
                (SELECT `kEinstellungenConf` FROM `teinstellungenconf` WHERE `cWertName` = 'global_ust_auszeichnung'),
                'Automatik: Inkl. / Exkl. USt. (ohne konkreten Steuersatz)',
                'autoNoVat',
                3)"
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->execute(
            "DELETE FROM `teinstellungenconfwerte`
                WHERE `kEinstellungenConf` =
                  (SELECT `kEinstellungenConf` FROM `teinstellungenconf` WHERE `cWertName` = 'global_ust_auszeichnung')
                  AND `cWert` = 'autoNoVat'"
        );
    }
}
