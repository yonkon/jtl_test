<?php
/**
 * Add language variables for the new pagination
 *
 * @author fm
 * @created Mon, 12 Sep 2016 17:30:00 +0200
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20160912173000
 */
class Migration_20160912173000 extends Migration implements IMigration
{
    protected $author = 'fm';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'global', 'paginationEntryPagination', 'Einträge %d &ndash; %d von %d');
        $this->setLocalization('eng', 'global', 'paginationEntryPagination', 'Entries %d &ndash; %d of %d');

        $this->setLocalization('ger', 'global', 'paginationEntriesPerPage', 'Einträge/Seite');
        $this->setLocalization('eng', 'global', 'paginationEntriesPerPage', 'Entries/page');

        $this->setLocalization('ger', 'global', 'asc', 'aufsteigend');
        $this->setLocalization('eng', 'global', 'asc', 'ascending');

        $this->setLocalization('ger', 'global', 'desc', 'absteigend');
        $this->setLocalization('eng', 'global', 'desc', 'descending');

        $this->setLocalization('ger', 'global', 'paginationTotalEntries', 'Eintr&auml;ge gesamt:');
        $this->setLocalization('eng', 'global', 'paginationTotalEntries', 'Total entries:');

        $this->setLocalization('ger', 'global', 'paginationOrderByDate', 'Datum');
        $this->setLocalization('eng', 'global', 'paginationOrderByDate', 'Date');

        $this->setLocalization('ger', 'global', 'paginationOrderByRating', 'Bewertung');
        $this->setLocalization('eng', 'global', 'paginationOrderByRating', 'Rating');

        $this->setLocalization('ger', 'global', 'paginationOrderUsefulness', 'Hilreich');
        $this->setLocalization('eng', 'global', 'paginationOrderUsefulness', 'Usefulness');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute(
            "DELETE FROM `tsprachwerte` 
                WHERE cName IN ('asc', 'desc', 'paginationTotalEntries', 'paginationEntriesPerPage',
                                'paginationEntryPagination', 'paginationOrderByDate', 'paginationOrderByRating',
                                'paginationOrderUsefulness')
                  AND kSprachsektion = 1"
        );
    }
}
