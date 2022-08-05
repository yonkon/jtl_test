<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20200903121000
 */
class Migration_20200903121000 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add country manager';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute("INSERT INTO `tadminrecht` (`cRecht`, `cBeschreibung`)
            VALUES ('COUNTRY_VIEW', 'Country manager')");

        $this->execute("ALTER TABLE `tland` ADD COLUMN `bPermitRegistration` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");
        $this->execute("ALTER TABLE `tland` ADD COLUMN `bRequireStateDefinition` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");

        if ((int)$this->getDB()->getSingleObject('SELECT COUNT(*) AS cnt FROM tversandart')->cnt === 0) {
            // if no shipping methods are set (most likely for new installation), permit all countries in European Economic Area
            $this->execute("UPDATE `tland` SET `bPermitRegistration` = 1 WHERE cISO IN ('BE', 'BG', 'DN', 'DE', 'EE', 'FI', "
                . "'FR', 'GR', 'IE', 'IT', 'HR', 'LV', 'LT', 'LU', 'MT', 'NL', 'AT', 'PL', 'PT', 'RO', 'SE', 'SK', 'SI', 'ES', "
                . "'CZ', 'HU', 'CY', 'IS', 'LI', 'NO')");
        } elseif (Shop::getSettingValue(CONF_KUNDEN, 'kundenregistrierung_nur_lieferlaender') === 'Y') {
            $this->execute(
                "UPDATE tland
                    INNER JOIN tversandart
                      ON tversandart.cLaender RLIKE CONCAT(tland.cISO, ' ')
                    SET tland.bPermitRegistration = 1
                    WHERE tland.bPermitRegistration = 0"
            );
        } else {
            $this->execute('UPDATE `tland` SET `bPermitRegistration` = 1');
        }
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute("DELETE FROM `tadminrecht` WHERE `cRecht` = 'COUNTRY_VIEW'");

        $this->execute('ALTER TABLE `tland` DROP COLUMN `bPermitRegistration`');
        $this->execute('ALTER TABLE `tland` DROP COLUMN `bRequireStateDefinition`');
    }
}
