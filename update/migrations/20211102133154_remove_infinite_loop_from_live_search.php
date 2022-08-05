<?php declare(strict_types=1);
/**
 * Remove looped live search
 *
 * @author fp
 * @created Tue, 02 Nov 2021 13:31:54 +0100
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20211102133154
 */
class Migration_20211102133154 extends Migration implements IMigration
{
    protected $author = 'fp';
    protected $description = 'Remove infinite loop from live search';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('DELETE FROM tsuchanfragemapping WHERE cSuche = cSucheNeu');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
    }
}
