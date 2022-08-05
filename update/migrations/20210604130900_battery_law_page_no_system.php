<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210604130900
 */
class Migration_20210604130900 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Set battery law page to no system page';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('UPDATE `tlink` SET bIsSystem = 0 WHERE nLinkart = ' . LINKTYP_BATTERIEGESETZ_HINWEISE);
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('UPDATE `tlink` SET bIsSystem = 1 WHERE nLinkart = ' . LINKTYP_BATTERIEGESETZ_HINWEISE);
    }
}
