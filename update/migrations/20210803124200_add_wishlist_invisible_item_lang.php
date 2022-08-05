<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210803124200
 */
class Migration_20210803124200 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add wishlist invisible item lang';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'wishlist', 'warningInvisibleItems', '%s Artikel sind derzeit nicht verfügbar '
            . 'und werden deshalb nicht angezeigt.');
        $this->setLocalization('eng', 'wishlist', 'warningInvisibleItems', '%s items are invisbile because they are '
            . 'not available.');
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function down()
    {
        $this->removeLocalization('warningInvisibleItems', 'wishlist');
    }
}
