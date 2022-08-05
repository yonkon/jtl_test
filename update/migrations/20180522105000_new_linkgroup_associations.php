<?php declare(strict_types=1);
/**
 * new link group associations
 *
 * @author fm
 * @created Thu, 22 May 2018 10:50:00 +0200
 */

use JTL\Helpers\Seo;
use JTL\Language\LanguageHelper;
use JTL\Update\IMigration;
use JTL\Update\Migration;
use function Functional\first;
use function Functional\group;

/**
 * Class Migration_20180522105000
 */
class Migration_20180522105000 extends Migration implements IMigration
{
    protected $author = 'fm';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->execute(
            'ALTER TABLE `tlinkgruppe`
                CHANGE COLUMN `kLinkgruppe` `kLinkgruppe` INT UNSIGNED NOT NULL AUTO_INCREMENT, ENGINE=InnoDB'
        );
        $this->execute(
            'ALTER TABLE `tlink` 
                CHANGE COLUMN `kLink` `kLink` INT UNSIGNED NOT NULL AUTO_INCREMENT, ENGINE=InnoDB'
        );
        $missingLanguageEntries = $this->getDB()->getObjects(
            "SELECT tlink.*, tseo.* 
                FROM tlink
                    LEFT JOIN tseo
                        ON tseo.cKey = 'kLink'
                        AND tseo.kKey = tlink.kLink
                WHERE kLink NOT IN (SELECT kLink FROM tlinksprache)"
        );
        $missingLanguageEntries = group($missingLanguageEntries, function ($e) {
            return $e->kLink;
        });
        $languages              = LanguageHelper::getAllLanguages();
        foreach ($missingLanguageEntries as $linkID => $links) {
            $linkData         = first($links);
            $localized        = new stdClass();
            $localized->kLink = $linkID;
            foreach ($languages as $language) {
                $match = first($links, function ($e) use ($language) {
                    return (int)$e->kSprache === $language->kSprache;
                });
                if ($match === null) {
                    // no seo entry exists for this language ID
                    $localized->cName = $linkData->cName;
                    $localized->cSeo  = $linkData->cName;
                } else {
                    $localized->cSeo  = $match->cSeo;
                    $localized->cName = $match->cName;
                }
                $localized->cISOSprache = $language->cISO;
                $localized->cTitle      = '';
                $localized->cContent    = '';
                $localized->cMetaTitle  = '';
                $localized->cSeo        = Seo::getSeo($localized->cSeo);
                $this->getDB()->insert('tlinksprache', $localized);
            }
        }
        $missingSeo = $this->getDB()->getObjects(
            "SELECT tlink.*, tlinksprache.*, tsprache.kSprache 
                FROM tlink
                JOIN tlinksprache
                    ON tlinksprache.kLink = tlink.kLink
                JOIN tsprache
                    ON tsprache.cISO = tlinksprache.cISOSprache
                LEFT JOIN tseo
                    ON tseo.cKey = 'kLink'
                    AND tseo.kKey = tlink.kLink 
                WHERE tseo.cSeo IS NULL"
        );
        foreach ($missingSeo as $item) {
            $seo           = new stdClass();
            $seo->cSeo     = Seo::checkSeo($item->cSeo);
            $seo->kKey     = $item->kLink;
            $seo->cKey     = 'kLink';
            $seo->kSprache = $item->kSprache;
            $this->getDB()->insert('tseo', $seo);
        }
        $missingLinkGroupLanguages = $this->getDB()->getObjects(
            'SELECT tlinkgruppe.* 
                FROM tlinkgruppe
                LEFT JOIN tlinkgruppesprache
                    ON tlinkgruppe.kLinkGruppe = tlinkgruppesprache.kLinkgruppe
                WHERE tlinkgruppesprache.kLinkgruppe IS NULL'
        );
        foreach ($missingLinkGroupLanguages as $missingLinkGroupLanguage) {
            foreach ($languages as $language) {
                $lang              = new stdClass();
                $lang->kLinkgruppe = $missingLinkGroupLanguage->kLinkgruppe;
                $lang->cName       = $missingLinkGroupLanguage->cName;
                $lang->cISOSprache = $language->cISO;
                $this->getDB()->insert('tlinkgruppesprache', $lang);
            }
        }
        $this->execute('CREATE TABLE `tlinkgroupassociations` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `linkID` INT UNSIGNED NOT NULL,
              `linkGroupID` INT UNSIGNED NOT NULL,
              PRIMARY KEY (`id`),
              INDEX `fk_tlinkgroupassociations_1_idx` (`linkID` ASC),
              CONSTRAINT `fk_tlinkGroupID`
                  FOREIGN KEY (`linkGroupID`)
                  REFERENCES `tlinkgruppe` (`kLinkgruppe`)
                  ON DELETE CASCADE
                  ON UPDATE CASCADE,
              CONSTRAINT `fk_tlinkID`
                  FOREIGN KEY (`linkID`)
                  REFERENCES `tlink` (`kLink`)
                  ON DELETE CASCADE
                  ON UPDATE CASCADE
              ) ENGINE=InnoDB COLLATE utf8_unicode_ci');
        $duplicates = $this->getDB()->getObjects(
            'SELECT *
                FROM tlink
                WHERE kLink IN (
                    SELECT kLink FROM tlink
                    GROUP BY klink
                    HAVING COUNT(*) > 1)
                ORDER BY kLink, kVaterLink, kLinkgruppe, nSort;'
        );
        $oldIDs     = [];
        $linkID     = 0;
        foreach ($duplicates as $duplicate) {
            if ($linkID !== (int)$duplicate->kLink) {
                $linkID = (int)$duplicate->kLink;
            } else {
                $this->getDB()->delete(
                    'tlink',
                    ['kLink', 'kVaterLink', 'kLinkgruppe'],
                    [$duplicate->kLink, $duplicate->kVaterLink, $duplicate->kLinkgruppe]
                );
                unset($duplicate->kLink);
                $duplicate->cName    = 'Referenz ' . $linkID;
                $duplicate->nLinkart = LINKTYP_REFERENZ;
                $newID               = $this->getDB()->insert('tlink', $duplicate);
                $oldIDs[$linkID]     = $newID;
            }
        }
        foreach ($oldIDs as $oldID => $newID) {
            $this->getDB()->queryPrepared(
                'UPDATE tlink SET kVaterLink = :parent WHERE kVaterLink = :oldParent',
                ['parent' => $newID, 'oldParent' => $oldID]
            );
        }
        $this->execute('INSERT INTO tlinkgroupassociations (`linkID`, `linkGroupID`) 
            (SELECT tlink.kLink, tlink.kLinkgruppe 
                FROM tlink 
                JOIN tlinkgruppe
                    ON tlinkgruppe.kLinkgruppe = tlink.kLinkgruppe)');
        $externalLinks = $this->getDB()->getObjects(
            "SELECT * FROM tlink
                WHERE tlink.cURL IS NOT NULL 
                  AND tlink.cURL != ''"
        );
        foreach ($externalLinks as $externalLink) {
            $this->getDB()->update(
                'tlinksprache',
                'kLink',
                $externalLink->kLink,
                (object)['cSeo' => $externalLink->cURL]
            );
        }
        $this->execute('ALTER TABLE `tlink` 
            DROP COLUMN `kLinkgruppe`,
            DROP COLUMN `cURL`,
            DROP PRIMARY KEY,
            ADD PRIMARY KEY (`kLink`)');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->execute('ALTER TABLE `tlink` ADD COLUMN `kLinkgruppe` TINYINT(3) UNSIGNED NOT NULL;');
        $this->execute('ALTER TABLE `tlink` ADD COLUMN `cURL` VARCHAR(255) DEFAULT NULL;');
        $assoc = $this->getDB()->getObjects(
            'SELECT linkID, linkGroupID 
                FROM tlinkgroupassociations'
        );
        foreach ($assoc as $item) {
            $this->getDB()->update(
                'tlink',
                'kLink',
                $item->linkID,
                (object)['kLinkgruppe' => $item->linkGroupID]
            );
        }
        $external = $this->getDB()->getObjects(
            'SELECT tlink.kLink, tlinksprache.cSeo
                FROM tlink 
                JOIN tlinksprache
                    ON tlink.kLink = tlinksprache.kLink
                WHERE tlink.nLinkart = 2
                GROUP BY tlink.kLink'
        );
        foreach ($external as $item) {
            $this->getDB()->update(
                'tlink',
                'kLink',
                $item->kLink,
                (object)['cURL' => $item->cSeo]
            );
        }
        $this->execute('DROP TABLE tlinkgroupassociations');
        $this->execute('ALTER TABLE `tlinkgruppe` CHANGE COLUMN `kLinkgruppe` `kLinkgruppe` TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT');
        $this->execute('ALTER TABLE tlink ADD INDEX `kLinkgruppe` (`kLinkgruppe`)');
        $this->execute('ALTER TABLE tlink CHANGE COLUMN `kLink` `kLink` INT NOT NULL');
        $this->execute('ALTER TABLE tlink DROP PRIMARY KEY');
        $this->execute('ALTER TABLE tlink ADD PRIMARY KEY (`kLink`,`kLinkgruppe`)');
        $this->execute('ALTER TABLE tlink CHANGE COLUMN `kLink` `kLink` INT NOT NULL AUTO_INCREMENT');
    }
}
