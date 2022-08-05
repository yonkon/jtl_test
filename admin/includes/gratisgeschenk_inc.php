<?php

use JTL\Catalog\Product\Artikel;
use JTL\Shop;

/**
 * @param string $sql
 * @return array
 */
function holeAktiveGeschenke(string $sql): array
{
    $res = [];
    if (mb_strlen($sql) < 1) {
        return $res;
    }
    $data = Shop::Container()->getDB()->getObjects(
        'SELECT kArtikel
            FROM tartikelattribut
            WHERE cName = :atr
            ORDER BY CAST(cWert AS SIGNED) DESC ' . $sql,
        ['atr' => ART_ATTRIBUT_GRATISGESCHENKAB]
    );

    $options                            = Artikel::getDefaultOptions();
    $options->nKeinLagerbestandBeachten = 1;
    foreach ($data as $item) {
        $product = new Artikel();
        $product->fuelleArtikel((int)$item->kArtikel, $options, 0, 0, true);
        if ($product->kArtikel > 0) {
            $res[] = $product;
        }
    }

    return $res;
}

/**
 * @param string $sql
 * @return array
 */
function holeHaeufigeGeschenke(string $sql): array
{
    $res = [];
    if (mb_strlen($sql) < 1) {
        return $res;
    }
    $data = Shop::Container()->getDB()->getObjects(
        'SELECT tgratisgeschenk.kArtikel, COUNT(*) AS nAnzahl, 
            MAX(tbestellung.dErstellt) AS lastOrdered, AVG(tbestellung.fGesamtsumme) AS avgOrderValue
            FROM tgratisgeschenk
            LEFT JOIN tbestellung
                ON tbestellung.kWarenkorb = tgratisgeschenk.kWarenkorb
            GROUP BY tgratisgeschenk.kArtikel
            ORDER BY nAnzahl DESC, lastOrdered DESC ' . $sql
    );

    $options                            = Artikel::getDefaultOptions();
    $options->nKeinLagerbestandBeachten = 1;
    foreach ($data as $item) {
        $product = new Artikel();
        $product->fuelleArtikel((int)$item->kArtikel, $options, 0, 0, true);
        if ($product->kArtikel > 0) {
            $product->nGGAnzahl = $item->nAnzahl;
            $res[]              = (object)[
                'artikel'       => $product,
                'lastOrdered'   => date_format(date_create($item->lastOrdered), 'd.m.Y H:i:s'),
                'avgOrderValue' => $item->avgOrderValue
            ];
        }
    }

    return $res;
}

/**
 * @param string $sql
 * @return array
 */
function holeLetzten100Geschenke(string $sql): array
{
    $res = [];
    if (mb_strlen($sql) < 1) {
        return $res;
    }
    $data                               = Shop::Container()->getDB()->getObjects(
        'SELECT tgratisgeschenk.*, tbestellung.dErstellt AS orderCreated, tbestellung.fGesamtsumme
            FROM tgratisgeschenk
              LEFT JOIN tbestellung 
                  ON tbestellung.kWarenkorb = tgratisgeschenk.kWarenkorb
            ORDER BY tbestellung.dErstellt DESC ' . $sql
    );
    $options                            = Artikel::getDefaultOptions();
    $options->nKeinLagerbestandBeachten = 1;
    foreach ($data as $item) {
        $product = new Artikel();
        $product->fuelleArtikel((int)$item->kArtikel, $options, 0, 0, true);
        if ($product->kArtikel > 0) {
            $product->nGGAnzahl = $item->nAnzahl;
            $res[]              = (object)[
                'artikel'      => $product,
                'orderCreated' => date_format(date_create($item->orderCreated), 'd.m.Y H:i:s'),
                'orderValue'   => $item->fGesamtsumme
            ];
        }
    }

    return $res;
}

/**
 * @return int
 */
function gibAnzahlAktiverGeschenke(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM tartikelattribut
            WHERE cName = :nm',
        ['nm' => ART_ATTRIBUT_GRATISGESCHENKAB]
    )->cnt;
}

/**
 * @return int
 */
function gibAnzahlHaeufigGekaufteGeschenke(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(DISTINCT(kArtikel)) AS cnt
            FROM twarenkorbpos
            WHERE nPosTyp = :tp',
        ['tp' => C_WARENKORBPOS_TYP_GRATISGESCHENK]
    )->cnt;
}

/**
 * @return int
 */
function gibAnzahlLetzten100Geschenke(): int
{
    return (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM twarenkorbpos
            WHERE nPosTyp = :tp
            LIMIT 100',
        ['tp' => C_WARENKORBPOS_TYP_GRATISGESCHENK]
    )->cnt;
}
