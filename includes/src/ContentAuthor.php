<?php

namespace JTL;

use stdClass;

/**
 * Class ContentAuthor
 * @package JTL
 */
class ContentAuthor
{
    use SingletonTrait;

    /**
     * @param string   $realm
     * @param int      $contentID
     * @param int|null $authorID
     * @return int|bool
     */
    public function setAuthor(string $realm, int $contentID, int $authorID = null)
    {
        if ($authorID === null || $authorID === 0) {
            $account = $GLOBALS['oAccount']->account();
            if ($account !== false) {
                $authorID = $account->kAdminlogin;
            }
        }
        if ($authorID > 0) {
            return Shop::Container()->getDB()->queryPrepared(
                'INSERT INTO tcontentauthor (cRealm, kAdminlogin, kContentId)
                    VALUES (:realm, :aid, :cid)
                    ON DUPLICATE KEY UPDATE kAdminlogin = :aid',
                ['realm' => $realm, 'aid' => $authorID, 'cid' => $contentID]
            );
        }

        return false;
    }

    /**
     * @param string $realm
     * @param int    $contentID
     */
    public function clearAuthor(string $realm, int $contentID): void
    {
        Shop::Container()->getDB()->delete('tcontentauthor', ['cRealm', 'kContentId'], [$realm, $contentID]);
    }

    /**
     * @param string $realm
     * @param int    $contentID
     * @param bool   $activeOnly
     * @return object|bool
     */
    public function getAuthor(string $realm, int $contentID, bool $activeOnly = false)
    {
        $filter = $activeOnly
            ? ' AND tadminlogin.bAktiv = 1
                AND COALESCE(tadminlogin.dGueltigBis, NOW()) >= NOW()'
            : '';
        $author = Shop::Container()->getDB()->getSingleObject(
            'SELECT tcontentauthor.kContentAuthor, tcontentauthor.cRealm, 
                tcontentauthor.kAdminlogin, tcontentauthor.kContentId,
                tadminlogin.cName, tadminlogin.cMail
                FROM tcontentauthor
                INNER JOIN tadminlogin 
                    ON tadminlogin.kAdminlogin = tcontentauthor.kAdminlogin
                WHERE tcontentauthor.cRealm = :realm
                    AND tcontentauthor.kContentId = :contentid' . $filter,
            ['realm' => $realm, 'contentid' => $contentID]
        );
        if ($author !== null && (int)$author->kAdminlogin > 0) {
            $attribs                = Shop::Container()->getDB()->getObjects(
                'SELECT tadminloginattribut.kAttribut, tadminloginattribut.cName, 
                    tadminloginattribut.cAttribValue, tadminloginattribut.cAttribText
                    FROM tadminloginattribut
                    WHERE tadminloginattribut.kAdminlogin = :aid',
                ['aid' => (int)$author->kAdminlogin]
            );
            $author->extAttribs     = [];
            $author->kContentId     = (int)$author->kContentId;
            $author->kContentAuthor = (int)$author->kContentAuthor;
            $author->kAdminlogin    = (int)$author->kAdminlogin;
            foreach ($attribs as $attrib) {
                $attrib->kAttribut                  = (int)$attrib->kAttribut;
                $author->extAttribs[$attrib->cName] = $attrib;
            }
        }

        return $author;
    }

    /**
     * @param array|null $adminRights
     * @return stdClass[]
     */
    public function getPossibleAuthors(array $adminRights = null): array
    {
        $filter = '';
        if ($adminRights !== null && \is_array($adminRights)) {
            $filter = " AND (tadminlogin.kAdminlogingruppe = 1
                        OR EXISTS (
                            SELECT 1 
                            FROM tadminrechtegruppe
                            WHERE tadminrechtegruppe.kAdminlogingruppe = tadminlogin.kAdminlogingruppe
                                AND tadminrechtegruppe.cRecht IN ('" . \implode("', '", $adminRights) . "')
                        ))";
        }

        return Shop::Container()->getDB()->getObjects(
            'SELECT tadminlogin.kAdminlogin, tadminlogin.cLogin, tadminlogin.cName, tadminlogin.cMail 
                FROM tadminlogin
                WHERE tadminlogin.bAktiv = 1
                    AND COALESCE(tadminlogin.dGueltigBis, NOW()) >= NOW()' . $filter
        );
    }
}
