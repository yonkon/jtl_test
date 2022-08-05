<?php declare(strict_types=1);
/**
 * change_vorausgewaehltes_land_conf_to_selectbox
 *
 * @author je
 * @created Fri, 19 Feb 2021 13:34:07 +0100
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210219133407
 */
class Migration_20210219133407 extends Migration implements IMigration
{
    protected $author = 'je';
    protected $description = 'change_vorausgewaehltes_land_conf_to_selectbox';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute("UPDATE teinstellungenconf SET cInputTyp='selectbox' WHERE cWertName = 'kundenregistrierung_standardland'");
        $this->execute("UPDATE teinstellungenconf SET cInputTyp='selectbox' WHERE cWertName = 'lieferadresse_abfragen_standardland'");
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute("UPDATE teinstellungenconf SET cInputTyp='text' WHERE cWertName = 'kundenregistrierung_standardland'");
        $this->execute("UPDATE teinstellungenconf SET cInputTyp='text' WHERE cWertName = 'lieferadresse_abfragen_standardland'");
    }
}
