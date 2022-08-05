<?php

use JTL\Update\IMigration;
use JTL\Update\Migration;
use JTL\Boxes\Admin\BoxAdmin;

/**
 * Class Migration_20200514090200
 */
class Migration_20200514090200 extends Migration implements IMigration
{
    protected $author      = 'dr';
    protected $description = 'Remove box visibilites of invalid/deprecated page types';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $boxAdmin  = new BoxAdmin($this->db);
        $pageTypes = $boxAdmin->getValidPageTypes();
        $pageTypes = implode(',', $pageTypes);
        $this->execute("DELETE FROM tboxensichtbar WHERE kSeite NOT IN ($pageTypes)");
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
    }
}
