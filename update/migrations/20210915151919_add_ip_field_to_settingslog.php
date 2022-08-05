<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210915151919
 */
class Migration_20210915151919 extends Migration implements IMigration
{
    protected $author = 'cr';
    protected $description = 'add_ip_field_to_settingslog';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute(
            "ALTER TABLE teinstellungenlog ADD COLUMN cIP varchar(40) AFTER cAdminname"
        );
    }

    /**
     * @inheritDor
     */
    public function down()
    {
        $this->execute(
            "ALTER TABLE teinstellungenlog DROP COLUMN cIP"
        );
    }
}
