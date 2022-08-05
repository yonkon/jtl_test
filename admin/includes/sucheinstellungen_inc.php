<?php

use JTL\Helpers\Text;
use JTL\IO\IOError;
use JTL\Shop;
use JTL\Shopsetting;

/**
 * @param string $index
 * @param string $create
 * @return array|IOError
 */
function createSearchIndex($index, $create)
{
    Shop::Container()->getGetText()->loadAdminLocale('pages/sucheinstellungen');
    require_once PFAD_ROOT . PFAD_INCLUDES . 'suche_inc.php';

    $index    = mb_convert_case(Text::xssClean($index), MB_CASE_LOWER);
    $notice   = '';
    $errorMsg = '';
    $db       = Shop::Container()->getDB();

    if (!in_array($index, ['tartikel', 'tartikelsprache'], true)) {
        return new IOError(__('errorIndexInvalid'), 403);
    }

    try {
        if ($db->getSingleObject("SHOW INDEX FROM $index WHERE KEY_NAME = 'idx_{$index}_fulltext'")) {
            $db->query("ALTER TABLE $index DROP KEY idx_{$index}_fulltext");
        }
    } catch (Exception $e) {
        // Fehler beim Index löschen ignorieren
    }

    if ($create === 'Y') {
        $searchRows = array_map(static function ($item) {
            $items = explode('.', $item, 2);

            return $items[1];
        }, JTL\Filter\States\BaseSearchQuery::getSearchRows());

        switch ($index) {
            case 'tartikel':
                $rows = array_intersect(
                    $searchRows,
                    [
                        'cName',
                        'cSeo',
                        'cSuchbegriffe',
                        'cArtNr',
                        'cKurzBeschreibung',
                        'cBeschreibung',
                        'cBarcode',
                        'cISBN',
                        'cHAN',
                        'cAnmerkung'
                    ]
                );
                break;
            case 'tartikelsprache':
                $rows = array_intersect($searchRows, ['cName', 'cSeo', 'cKurzBeschreibung', 'cBeschreibung']);
                break;
            default:
                return new IOError(__('errorIndexInvalid'), 403);
        }

        try {
            $db->query('UPDATE tsuchcache SET dGueltigBis = DATE_ADD(NOW(), INTERVAL 10 MINUTE)');
            $res = $db->getPDOStatement(
                "ALTER TABLE $index
                    ADD FULLTEXT KEY idx_{$index}_fulltext (" . implode(', ', $rows) . ')'
            );
        } catch (Exception $e) {
            $res = 0;
        }

        if ($res === 0) {
            $errorMsg     = __('errorIndexNotCreatable');
            $shopSettings = Shopsetting::getInstance();
            $settings     = $shopSettings[Shopsetting::mapSettingName(CONF_ARTIKELUEBERSICHT)];

            if ($settings['suche_fulltext'] !== 'N') {
                $settings['suche_fulltext'] = 'N';
                saveAdminSectionSettings(CONF_ARTIKELUEBERSICHT, $settings);

                Shop::Container()->getCache()->flushTags([
                    CACHING_GROUP_OPTION,
                    CACHING_GROUP_CORE,
                    CACHING_GROUP_ARTICLE,
                    CACHING_GROUP_CATEGORY
                ]);
                $shopSettings->reset();
            }
        } else {
            $notice = sprintf(__('successIndexCreate'), $index);
        }
    } else {
        $notice = sprintf(__('successIndexDelete'), $index);
    }

    return $errorMsg !== '' ? new IOError($errorMsg) : ['hinweis' => $notice];
}

/**
 * @return array
 */
function clearSearchCache(): array
{
    Shop::Container()->getDB()->query('DELETE FROM tsuchcachetreffer');
    Shop::Container()->getDB()->query('DELETE FROM tsuchcache');
    Shop::Container()->getGetText()->loadAdminLocale('pages/sucheinstellungen');

    return ['hinweis' => __('successSearchCacheDelete')];
}
