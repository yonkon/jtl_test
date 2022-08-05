<?php

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210823135200
 */
class Migration_20210823135200 extends Migration implements IMigration
{
    protected $author      = 'fp';
    protected $description = 'Add AdminName to log table';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute(
            "ALTER TABLE teinstellungenlog
                ADD COLUMN cAdminname VARCHAR(255) NOT NULL DEFAULT '' AFTER kAdminlogin"
        );
        /** @noinspection SqlWithoutWhere */
        $this->execute(
            "UPDATE teinstellungenlog SET cAdminname = COALESCE(
                (SELECT cName FROM tadminlogin WHERE tadminlogin.kAdminlogin = teinstellungenlog.kAdminlogin),
                'unknown'
            )"
        );
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute(
            'ALTER TABLE teinstellungenlog
                DROP COLUMN cAdminname'
        );
    }
}
