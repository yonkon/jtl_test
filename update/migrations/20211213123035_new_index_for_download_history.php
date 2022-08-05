<?php declare(strict_types=1);
/**
 * New index for download history
 *
 * @author fp
 * @created Mon, 13 Dec 2021 12:30:35 +0100
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20211213123035
 */
class Migration_20211213123035 extends Migration implements IMigration
{
    protected $author = 'fp';
    protected $description = 'New index for download history';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute('ALTER TABLE tdownloadhistory ADD INDEX idx_download (kDownload)');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('ALTER TABLE tdownloadhistory DROP INDEX idx_download');
    }
}
