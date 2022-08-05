<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20200204112200
 */
class Migration_20200204112200 extends Migration implements IMigration
{
    protected $author      = 'fm';
    protected $description = 'Remove global meta keywords';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->execute('DROP TABLE texcludekeywords');
        $this->execute("DELETE FROM tglobalemetaangaben WHERE cName = 'Meta_Keywords'");
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->execute(
            'CREATE TABLE `texcludekeywords` (
                  `cISOSprache` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
                  `cKeywords` mediumtext COLLATE utf8_unicode_ci DEFAULT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;'
        );
        $this->execute("INSERT INTO `texcludekeywords` 
            VALUES ('ger','aus ohne mit der die das zur für in einer eine einem sein seine'),
                   ('eng','with without it in out')");
    }
}
