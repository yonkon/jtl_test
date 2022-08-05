<?php

namespace JTL\Helpers;

use JTL\Shop;

/**
 * Class SeoHelper
 * @package JTL\Helpers
 */
class Seo
{
    /**
     * @param string $url
     * @param bool $keepUnderscore
     * @return string
     */
    public static function getSeo($url, bool $keepUnderscore = false): string
    {
        return \is_string($url) ? self::sanitizeSeoSlug($url, $keepUnderscore) : '';
    }

    /**
     * @param string $url
     * @return string
     */
    public static function checkSeo($url): string
    {
        if (!$url || !\is_string($url)) {
            return '';
        }
        $exists = Shop::Container()->getDB()->select('tseo', 'cSeo', $url);
        if ($exists === null) {
            return $url;
        }
        Shop::Container()->getDB()->query('SET @IKEY := 0');
        $obj = Shop::Container()->getDB()->getSingleObject(
            "SELECT oseo.newSeo
                FROM (
                    SELECT CONCAT('{$url}', '_', (CONVERT(@IKEY:=@IKEY+1 USING 'utf8') COLLATE utf8_unicode_ci)) newSeo,
                        @IKEY nOrder
                    FROM tseo AS iseo
                    WHERE iseo.cSeo LIKE '{$url}%'
                        AND iseo.cSeo RLIKE '^{$url}(_[0-9]+)?$'
                ) AS oseo
                WHERE oseo.newSeo NOT IN (
                    SELECT iseo.cSeo
                    FROM tseo AS iseo
                    WHERE iseo.cSeo LIKE '{$url}_%'
                        AND iseo.cSeo RLIKE '^{$url}_[0-9]+$'
                )
                ORDER BY oseo.nOrder
                LIMIT 1"
        );

        return $obj->newSeo ?? $url;
    }

    /**
     * @param string $str
     * @param bool $keepUnderscore
     * @return string
     */
    public static function sanitizeSeoSlug(string $str, bool $keepUnderscore = false): string
    {
        $str          = \preg_replace('/[^\pL\d\-\/_\s]+/u', '', Text::replaceUmlauts($str));
        $str          = \preg_replace('/[\/]+/u', '/', $str);
        $str          = \transliterator_transliterate(
            'Any-Latin; Latin-ASCII;' . (\SEO_SLUG_LOWERCASE ? ' Lower();' : ''),
            \trim($str, ' -_')
        );
        $convertedStr = @\iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str          = $convertedStr === false ? \preg_replace('/[^a-zA-Z0-9\s]/', '', $str) : $convertedStr;

        return $keepUnderscore === false ?
            \preg_replace('/[\-_\s]+/u', '-', \trim($str)) :
            \preg_replace('/[\-\s]+/u', '-', \trim($str));
    }

    /**
     * Get flat SEO-URL path (removes all slashes from seo-url-path, including leading and trailing slashes)
     *
     * @param string $path - the seo path e.g. "My/Product/Name"
     * @return string - flat SEO-URL Path e.g. "My-Product-Name"
     */
    public static function getFlatSeoPath($path): string
    {
        return \trim(\str_replace('/', '-', $path), ' -_');
    }
}
