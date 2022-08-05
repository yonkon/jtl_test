<?php declare(strict_types=1);
/**
 * Create index for statistics
 *
 * @author fp
 * @created Tue, 02 Nov 2021 09:33:40 +0100
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20211102093340
 */
class Migration_20211102093340 extends Migration implements IMigration
{
    protected $author      = 'fp';
    protected $description = /** @lang text */ 'Create index for statistics';

    /**
     * @inheritDoc
     */
    public function up()
    {
        if ($this->fetchOne("SHOW INDEX FROM tbesucherarchiv WHERE KEY_NAME = 'idx_kBot_dZeit'")) {
            $this->execute('DROP INDEX idx_kBot_dZeit ON tbesucherarchiv');
        }
        if ($this->fetchOne("SHOW INDEX FROM tbesucher WHERE KEY_NAME = 'idx_kBot_dZeit'")) {
            $this->execute('DROP INDEX idx_kBot_dZeit ON tbesucher');
        }
        $this->execute('ALTER TABLE tbesucherarchiv ADD INDEX idx_kBot_dZeit (kBesucherBot, dZeit)');
        $this->execute('ALTER TABLE tbesucher ADD INDEX idx_kBot_dZeit (kBesucherBot, dZeit)');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('DROP INDEX idx_kBot_dZeit ON tbesucherarchiv');
        $this->execute('DROP INDEX idx_kBot_dZeit ON tbesucher');
    }
}
