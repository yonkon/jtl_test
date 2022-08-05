<?php
/**
 * added_new_group_description_tab_and_sorting_options
 *
 * @author msc
 * @created Fri, 13 May 2016 16:24:42 +0200
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20160513162442
 */
class Migration_20160513162442 extends Migration implements IMigration
{
    protected $author = 'msc';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute(
            "INSERT INTO `teinstellungenconf` 
              (`kEinstellungenConf`,`kEinstellungenSektion`,`cName`,`cBeschreibung`,
               `cWertName`,`cInputTyp`,`cModulId`,`nSort`,`nStandardAnzeigen`,`nModul`,`cConf`)
               VALUES (1650,5,'Beschreibungs-Tab','',NULL,NULL,NULL,1450,1,0,'N')"
        );
        $this->execute('UPDATE `teinstellungenconf` SET nSort=1470 WHERE kEinstellungenConf=191');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=1480 WHERE kEinstellungenConf=496');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=1460 WHERE kEinstellungenConf=482');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=1500 WHERE kEinstellungenConf=219');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('DELETE FROM `teinstellungenconf` WHERE `teinstellungenconf`.`kEinstellungenConf` = 1650');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=400 WHERE kEinstellungenConf=191');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=402 WHERE kEinstellungenConf=496');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=498 WHERE kEinstellungenConf=482');
        $this->execute('UPDATE `teinstellungenconf` SET nSort=420 WHERE kEinstellungenConf=219');
    }
}
