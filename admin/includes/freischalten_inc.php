<?php

use JTL\Customer\Customer;
use JTL\Helpers\Seo;
use JTL\Review\ReviewAdminController;
use JTL\Shop;

/**
 * @param string   $sql
 * @param stdClass $searchSQL
 * @param bool     $checkLanguage
 * @return stdClass[]
 */
function gibBewertungFreischalten(string $sql, stdClass $searchSQL, bool $checkLanguage = true): array
{
    $cond = $checkLanguage === true
        ? 'tbewertung.kSprache = ' . (int)$_SESSION['editLanguageID'] . ' AND '
        : '';

    return Shop::Container()->getDB()->getObjects(
        "SELECT tbewertung.*, DATE_FORMAT(tbewertung.dDatum, '%d.%m.%Y') AS Datum, tartikel.cName AS ArtikelName
            FROM tbewertung
            LEFT JOIN tartikel 
                ON tbewertung.kArtikel = tartikel.kArtikel
            WHERE " . $cond . 'tbewertung.nAktiv = 0
                ' . $searchSQL->cWhere . '
            ORDER BY tbewertung.kArtikel, tbewertung.dDatum DESC' . $sql
    );
}

/**
 * @param string   $sql
 * @param stdClass $searchSQL
 * @param bool     $checkLanguage
 * @return stdClass[]
 */
function gibSuchanfrageFreischalten(string $sql, stdClass $searchSQL, bool $checkLanguage = true): array
{
    $cond = $checkLanguage === true
        ? 'AND kSprache = ' . (int)$_SESSION['editLanguageID'] . ' '
        : '';

    return Shop::Container()->getDB()->getObjects(
        "SELECT *, DATE_FORMAT(dZuletztGesucht, '%d.%m.%Y %H:%i') AS dZuletztGesucht_de
            FROM tsuchanfrage
            WHERE nAktiv = 0 " . $cond . $searchSQL->cWhere . '
            ORDER BY ' . $searchSQL->cOrder . $sql
    );
}

/**
 * @param string   $sql
 * @param stdClass $searchSQL
 * @param bool     $checkLanguage
 * @return stdClass[]
 */
function gibNewskommentarFreischalten(string $sql, stdClass $searchSQL, bool $checkLanguage = true): array
{
    $cond         = $checkLanguage === true
        ? ' AND t.languageID = ' . (int)$_SESSION['editLanguageID'] . ' '
        : '';
    $newsComments = Shop::Container()->getDB()->getObjects(
        "SELECT tnewskommentar.*, DATE_FORMAT(tnewskommentar.dErstellt, '%d.%m.%Y  %H:%i') AS dErstellt_de, 
            tkunde.kKunde, tkunde.cVorname, tkunde.cNachname, t.title AS cBetreff
            FROM tnewskommentar
            JOIN tnews 
                ON tnews.kNews = tnewskommentar.kNews
            JOIN tnewssprache t 
                ON tnews.kNews = t.kNews
            LEFT JOIN tkunde 
                ON tkunde.kKunde = tnewskommentar.kKunde
            WHERE tnewskommentar.nAktiv = 0" .
            $searchSQL->cWhere . $cond . $sql
    );
    foreach ($newsComments as $comment) {
        $customer = new Customer(isset($comment->kKunde) ? (int)$comment->kKunde : null);

        $comment->cNachname = $customer->cNachname;
    }

    return $newsComments;
}

/**
 * @param string   $sql
 * @param stdClass $searchSQL
 * @param bool     $checkLanguage
 * @return stdClass[]
 */
function gibNewsletterEmpfaengerFreischalten(string $sql, stdClass $searchSQL, bool $checkLanguage = true): array
{
    $cond = $checkLanguage === true
        ? ' AND kSprache = ' . (int)$_SESSION['editLanguageID']
        : '';

    return Shop::Container()->getDB()->getObjects(
        "SELECT *, DATE_FORMAT(dEingetragen, '%d.%m.%Y  %H:%i') AS dEingetragen_de, 
            DATE_FORMAT(dLetzterNewsletter, '%d.%m.%Y  %H:%i') AS dLetzterNewsletter_de
            FROM tnewsletterempfaenger
            WHERE nAktiv = 0
                " . $searchSQL->cWhere . $cond .
        ' ORDER BY ' . $searchSQL->cOrder . $sql
    );
}

/**
 * @param array $reviewIDs
 * @return bool
 */
function schalteBewertungFrei(array $reviewIDs): bool
{
    if (count($reviewIDs) === 0) {
        return false;
    }
    $controller = new ReviewAdminController(Shop::Container()->getDB(), Shop::Container()->getCache());
    $controller->activate($reviewIDs);

    return true;
}

/**
 * @param array $searchQueries
 * @return bool
 */
function schalteSuchanfragenFrei(array $searchQueries): bool
{
    if (count($searchQueries) === 0) {
        return false;
    }
    $db = Shop::Container()->getDB();
    foreach (array_map('\intval', $searchQueries) as $qid) {
        $query = $db->getSingleObject(
            'SELECT kSuchanfrage, kSprache, cSuche
                FROM tsuchanfrage
                WHERE kSuchanfrage = :qid',
            ['qid' => $qid]
        );
        if ($query !== null && $query->kSuchanfrage > 0) {
            $db->delete(
                'tseo',
                ['cKey', 'kKey', 'kSprache'],
                ['kSuchanfrage', $qid, (int)$query->kSprache]
            );
            $seo           = new stdClass();
            $seo->cSeo     = Seo::checkSeo(Seo::getSeo($query->cSuche));
            $seo->cKey     = 'kSuchanfrage';
            $seo->kKey     = $qid;
            $seo->kSprache = (int)$query->kSprache;
            $db->insert('tseo', $seo);
            $db->update(
                'tsuchanfrage',
                'kSuchanfrage',
                $qid,
                (object)['nAktiv' => 1, 'cSeo' => $seo->cSeo]
            );
        }
    }

    return true;
}

/**
 * @param array $newsComments
 * @return bool
 */
function schalteNewskommentareFrei(array $newsComments): bool
{
    if (count($newsComments) === 0) {
        return false;
    }
    Shop::Container()->getDB()->query(
        'UPDATE tnewskommentar
            SET nAktiv = 1
            WHERE kNewsKommentar IN (' . implode(',', array_map('\intval', $newsComments)) . ')'
    );

    return true;
}

/**
 * @param array $recipients
 * @return bool
 */
function schalteNewsletterempfaengerFrei(array $recipients): bool
{
    if (count($recipients) === 0) {
        return false;
    }
    Shop::Container()->getDB()->query(
        'UPDATE tnewsletterempfaenger
            SET nAktiv = 1
            WHERE kNewsletterEmpfaenger IN (' . implode(',', array_map('\intval', $recipients)) .')'
    );

    return true;
}

/**
 * @param array $ratings
 * @return bool
 */
function loescheBewertung(array $ratings): bool
{
    if (count($ratings) === 0) {
        return false;
    }
    Shop::Container()->getDB()->query(
        'DELETE FROM tbewertung
            WHERE kBewertung IN (' . implode(',', array_map('\intval', $ratings)) . ')'
    );

    return true;
}

/**
 * @param array $queries
 * @return bool
 */
function loescheSuchanfragen(array $queries): bool
{
    if (count($queries) === 0) {
        return false;
    }
    $queries = array_map('\intval', $queries);

    Shop::Container()->getDB()->query(
        'DELETE FROM tsuchanfrage
            WHERE kSuchanfrage IN (' . implode(',', $queries) . ')'
    );
    Shop::Container()->getDB()->query(
        "DELETE FROM tseo
            WHERE cKey = 'kSuchanfrage'
                AND kKey IN (" . implode(',', $queries) . ')'
    );

    return true;
}

/**
 * @param array $comments
 * @return bool
 */
function loescheNewskommentare(array $comments): bool
{
    if (count($comments) === 0) {
        return false;
    }
    Shop::Container()->getDB()->query(
        'DELETE FROM tnewskommentar
            WHERE kNewsKommentar IN (' . implode(',', array_map('\intval', $comments)) . ')'
    );

    return true;
}

/**
 * @param array $recipients
 * @return bool
 */
function loescheNewsletterempfaenger(array $recipients): bool
{
    if (count($recipients) === 0) {
        return false;
    }
    Shop::Container()->getDB()->query(
        'DELETE FROM tnewsletterempfaenger
            WHERE kNewsletterEmpfaenger IN (' . implode(',', array_map('\intval', $recipients)) . ')'
    );

    return true;
}

/**
 * @param array|mixed $queryIDs
 * @param string      $mapTo
 * @return int
 */
function mappeLiveSuche($queryIDs, string $mapTo): int
{
    if (!is_array($queryIDs) || count($queryIDs) === 0 || mb_strlen($mapTo) === 0) {
        return 2; // Leere Übergabe
    }
    $db = Shop::Container()->getDB();
    foreach (array_map('\intval', $queryIDs) as $queryID) {
        $query = $db->select('tsuchanfrage', 'kSuchanfrage', $queryID);
        if ($query === null || empty($query->kSuchanfrage)) {
            return 3; // Mindestens eine Suchanfrage wurde nicht in der Datenbank gefunden.
        }
        if (mb_convert_case($query->cSuche, MB_CASE_LOWER) === mb_convert_case($mapTo, MB_CASE_LOWER)) {
            return 6; // Es kann nicht auf sich selbst gemappt werden
        }
        $oSuchanfrageNeu = $db->select('tsuchanfrage', 'cSuche', $mapTo);
        if ($oSuchanfrageNeu === null || empty($oSuchanfrageNeu->kSuchanfrage)) {
            return 5; // Sie haben versucht auf eine nicht existierende Suchanfrage zu mappen
        }
        $mapping                 = new stdClass();
        $mapping->kSprache       = $_SESSION['editLanguageID'];
        $mapping->cSuche         = $query->cSuche;
        $mapping->cSucheNeu      = $mapTo;
        $mapping->nAnzahlGesuche = $query->nAnzahlGesuche;

        $kSuchanfrageMapping = $db->insert('tsuchanfragemapping', $mapping);

        if (empty($kSuchanfrageMapping)) {
            return 4; // Mapping konnte nicht gespeichert werden
        }
        $db->queryPrepared(
            'UPDATE tsuchanfrage
                SET nAnzahlGesuche = nAnzahlGesuche + :cnt
                WHERE kSprache = :lid
                    AND kSuchanfrage = :sid',
            [
                'cnt' => $query->nAnzahlGesuche,
                'lid' => (int)$_SESSION['editLanguageID'],
                'sid' => (int)$oSuchanfrageNeu->kSuchanfrage
            ]
        );
        $db->delete('tsuchanfrage', 'kSuchanfrage', (int)$query->kSuchanfrage);
        $db->queryPrepared(
            "UPDATE tseo
                SET kKey = :sqid
                WHERE cKey = 'kSuchanfrage'
                    AND kKey = :sqid",
            ['sqid' => (int)$query->kSuchanfrage]
        );
    }

    return 1;
}

/**
 * @return int
 */
function gibMaxBewertungen(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tbewertung
            WHERE nAktiv = 0
                AND kSprache = :lid',
        ['lid' => (int)$_SESSION['editLanguageID']]
    )->cnt;
}

/**
 * @return int
 */
function gibMaxSuchanfragen(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tsuchanfrage
            WHERE nAktiv = 0
                AND kSprache = :lid',
        ['lid' => (int)$_SESSION['editLanguageID']]
    )->cnt;
}

/**
 * @return int
 */
function gibMaxNewskommentare(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(tnewskommentar.kNewsKommentar) AS cnt
            FROM tnewskommentar
            JOIN tnews 
                ON tnews.kNews = tnewskommentar.kNews
            JOIN tnewssprache t 
                ON tnews.kNews = t.kNews
            WHERE tnewskommentar.nAktiv = 0
                AND t.languageID = :lid',
        ['lid' => (int)$_SESSION['editLanguageID']],
    )->cnt;
}

/**
 * @return int
 */
function gibMaxNewsletterEmpfaenger(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tnewsletterempfaenger
            WHERE nAktiv = 0
                AND kSprache = :lid',
        ['lid' => (int)$_SESSION['editLanguageID']],
    )->cnt;
}
