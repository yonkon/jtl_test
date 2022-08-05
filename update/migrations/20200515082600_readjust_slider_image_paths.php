<?php declare(strict_types=1);

use JTL\Shop;
use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20200515082600
 */
class Migration_20200515082600 extends Migration implements IMigration
{
    protected $author      = 'dr';
    protected $description = 'Readjust slider image paths';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $mediafilesPath = PFAD_MEDIAFILES;

        $this->execute(
            "UPDATE tslide
                SET cBild = CONCAT('$mediafilesPath', cBild),
                    cThumbnail = CONCAT('$mediafilesPath', 'Bilder/.tmb/', substring_index(cBild, '/', -1))
                WHERE cBild LIKE 'Bilder/%'"
        );

        $shopPath = parse_url(Shop::getURL() . '/', PHP_URL_PATH);

        $this->execute(
            "UPDATE tslide
                SET cBild = TRIM(LEADING '$shopPath' FROM cBild)
                WHERE cBild LIKE '$shopPath%'"
        );
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
    }
}
