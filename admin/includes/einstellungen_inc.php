<?php

use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Shop;
use function Functional\filter;
use function Functional\flatten;

require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'admin_menu.php';

/**
 * @param string $query
 * @param bool   $save
 * @return stdClass
 */
function bearbeiteEinstellungsSuche(string $query, bool $save = false): stdClass
{
    $result = (object)[
        'cSearch'          => '',
        'cWHERE'           => '',
        'nSuchModus'       => 0,
        'cSuche'           => $query,
        'oEinstellung_arr' => [],
    ];
    if (mb_strlen($query) === 0) {
        return $result;
    }
    $result->cWHERE = "(ec.cModulId IS NULL OR ec.cModulId = '') AND ec.kEinstellungenSektion != 101 ";
    $idList         = explode(',', $query);
    $isIdList       = count($idList) > 1;
    if ($isIdList) {
        foreach ($idList as $i => $item) {
            $idList[$i] = (int)$item;

            if ($idList[$i] === 0) {
                $isIdList = false;
                break;
            }
        }
    }

    if ($isIdList) {
        $result->nSuchModus = 1;
        $result->cSearch    = sprintf(__('searchForID'), implode(', ', $idList));
        $result->cWHERE    .= ' AND kEinstellungenConf IN (' . implode(', ', $idList) . ')';
        $result->confIds    = $idList;
    } else {
        $rangeList = explode('-', $query);
        $isIdRange = count($rangeList) === 2;
        if ($isIdRange) {
            $rangeList[0] = (int)$rangeList[0];
            $rangeList[1] = (int)$rangeList[1];
            if ($rangeList[0] === 0 || $rangeList[1] === 0) {
                $isIdRange = false;
            }
        }
        if ($isIdRange) {
            $result->nSuchModus = 2;
            $result->cSearch    = sprintf(__('searchForIDRange'), $rangeList[0] . ' - ' . $rangeList[1]);
            $result->cWHERE    .= ' AND kEinstellungenConf BETWEEN ' . $rangeList[0] . ' AND ' . $rangeList[1];
            $result->cWHERE    .= " AND cConf = 'Y'";
            $result->confIdFrom = $rangeList[0];
            $result->confIdTo   = $rangeList[1];
        } elseif ((int)$query > 0) {
            $result->nSuchModus = 3;
            $result->cSearch    = sprintf(__('searchForID'), $query);
            $result->cWHERE    .= ' AND kEinstellungenConf = ' . (int)$query;
        } else {
            $query              = mb_convert_case($query, MB_CASE_LOWER);
            $queryEnt           = Text::htmlentities($query);
            $result->nSuchModus = 4;
            $result->cSearch    = sprintf(__('searchForName'), $query);
            $getText            = Shop::Container()->getGetText();
            $configTranslations = $getText->getAdminTranslations('configs/configs');
            $valueNames         = [];
            foreach ($configTranslations->getIterator() as $translation) {
                $orig  = $translation->getOriginal();
                $trans = $translation->getTranslation();
                if ((mb_stripos($trans, $query) !== false || mb_stripos($trans, $queryEnt) !== false)
                    && mb_substr($orig, -5) === '_name'
                ) {
                    $valueName    = preg_replace('/(_name|_desc)$/', '', $orig);
                    $valueNames[] = "'" . $valueName . "'";
                }
            }
            $result->cWHERE .= ' AND cWertName IN (' . (implode(', ', $valueNames) ?: "''") . ')';
            $result->cWHERE .= " AND cConf = 'Y'";
        }
    }

    return holeEinstellungen($result, $save);
}

/**
 * @param stdClass $sql
 * @param bool   $save
 * @return stdClass
 */
function holeEinstellungen(stdClass $sql, bool $save): stdClass
{
    if (mb_strlen($sql->cWHERE) <= 0) {
        return $sql;
    }
    $sql->oEinstellung_arr = Shop::Container()->getDB()->getObjects(
        'SELECT ec.*, e.cWert AS currentValue, ed.cWert AS defaultValue
            FROM teinstellungenconf AS ec
            LEFT JOIN teinstellungen AS e
              ON e.cName = ec.cWertName
            LEFT JOIN teinstellungen_default AS ed
              ON ed.cName = ec.cWertName
            WHERE ' . $sql->cWHERE . '
            ORDER BY ec.kEinstellungenSektion, nSort'
    );
    Shop::Container()->getGetText()->loadConfigLocales();
    foreach ($sql->oEinstellung_arr as $config) {
        Shop::Container()->getGetText()->localizeConfig($config);
        if ((int)$sql->nSuchModus === 3 && $config->cConf === 'Y') {
            $sql->oEinstellung_arr = [];
            $configHead            = holeEinstellungHeadline(
                (int)$config->nSort,
                (int)$config->kEinstellungenSektion
            );
            if (isset($configHead->kEinstellungenConf) && $configHead->kEinstellungenConf > 0) {
                $sql->oEinstellung_arr[] = $configHead;
                $sql                     = holeEinstellungAbteil(
                    $sql,
                    $configHead->nSort,
                    $configHead->kEinstellungenSektion
                );
            }
        } elseif ($config->cConf === 'N') {
            $sql = holeEinstellungAbteil($sql, (int)$config->nSort, (int)$config->kEinstellungenSektio);
        }
    }
    // Aufräumen
    if (count($sql->oEinstellung_arr) > 0) {
        $configIDs = [];
        foreach ($sql->oEinstellung_arr as $i => $config) {
            $config->kEinstellungenConf = (int)$config->kEinstellungenConf;
            if (isset($config->kEinstellungenConf)
                && $config->kEinstellungenConf > 0
                && !in_array($config->kEinstellungenConf, $configIDs, true)
            ) {
                $configIDs[$i] = $config->kEinstellungenConf;
            } else {
                unset($sql->oEinstellung_arr[$i]);
            }

            if ($save && $config->cConf === 'N') {
                unset($sql->oEinstellung_arr[$i]);
            }
        }
        $sql->oEinstellung_arr = sortiereEinstellungen($sql->oEinstellung_arr);
    }

    return $sql;
}

/**
 * @param stdClass $sql
 * @param int    $sort
 * @param int    $sectionID
 * @return stdClass
 */
function holeEinstellungAbteil(stdClass $sql, int $sort, int $sectionID): stdClass
{
    if ($sort <= 0 || $sectionID <= 0) {
        return $sql;
    }
    $items = Shop::Container()->getDB()->getObjects(
        'SELECT ec.*, e.cWert AS currentValue, ed.cWert AS defaultValue
            FROM teinstellungenconf AS ec
            LEFT JOIN teinstellungen AS e
              ON e.cName = ec.cWertName
            LEFT JOIN teinstellungen_default AS ed
              ON ed.cName = ec.cWertName
            WHERE ec.nSort > :srt
                AND ec.kEinstellungenSektion = :sid
            ORDER BY ec.nSort',
        ['srt' => $sort, 'sid' => $sectionID]
    );
    foreach ($items as $item) {
        if ($item->cConf !== 'N') {
            $sql->oEinstellung_arr[] = $item;
        } else {
            break;
        }
    }

    return $sql;
}

/**
 * @param int $sort
 * @param int $sectionID
 * @return stdClass
 */
function holeEinstellungHeadline(int $sort, int $sectionID): stdClass
{
    $configHead = new stdClass();
    if ($sort <= 0 || $sectionID <= 0) {
        return $configHead;
    }
    $item = Shop::Container()->getDB()->getSingleObject(
        "SELECT *
            FROM teinstellungenconf
            WHERE nSort < :srt
                AND kEinstellungenSektion = :sid
                AND cConf = 'N'
            ORDER BY nSort DESC",
        ['srt' => $sort, 'sid' => $sectionID]
    );
    if ($item !== null) {
        $item->kEinstellungenConf    = (int)$item->kEinstellungenConf;
        $item->kEinstellungenSektion = (int)$item->kEinstellungenSektion;
        $item->nSort                 = (int)$item->nSort;
        $item->nStandardAnzeigen     = (int)$item->nStandardAnzeigen;

        $menuEntry                  = mapConfigSectionToMenuEntry($sectionID, $item->cWertName);
        $configHead                 = $item;
        $configHead->cSektionsPfad  = getConfigSectionPath($menuEntry);
        $configHead->cURL           = getConfigSectionUrl($menuEntry);
        $configHead->specialSetting = isConfigSectionSpecialSetting($menuEntry);
        $configHead->settingsAnchor = getConfigSectionAnchor($menuEntry);
    }

    return $configHead;
}

/**
 * @param int   $sectionID
 * @param mixed $groupName
 * @return string
 * @deprecated since 5.0.2
 */
function gibEinstellungsSektionsPfad(int $sectionID, $groupName): string
{
    return getConfigSectionPath(mapConfigSectionToMenuEntry($sectionID, $groupName));
}

/**
 * @param int   $sectionID
 * @param mixed $groupName
 * @return string
 * @deprecated since 5.0.2
 */
function getSectionMenuPath(int $sectionID, $groupName): string
{
    return getConfigSectionUrl(mapConfigSectionToMenuEntry($sectionID, $groupName));
}

/**
 * @param int $sectionID
 * @return boolean
 * @deprecated since 5.0.2
 */
function getSpecialSetting(int $sectionID, $groupName): bool
{
    return isConfigSectionSpecialSetting(mapConfigSectionToMenuEntry($sectionID, $groupName));
}

/**
 * @param int   $sectionID
 * @param mixed $groupName
 * @return string
 * @deprecated since 5.0.2
 */
function getSettingsAnchor(int $sectionID, $groupName): string
{
    return getConfigSectionAnchor(mapConfigSectionToMenuEntry($sectionID, $groupName));
}

/**
 * @param int $sectionID
 * @param string $groupName
 * @return stdClass
 */
function mapConfigSectionToMenuEntry(int $sectionID, string $groupName = 'all')
{
    global $sectionMenuMapping;

    if (isset($sectionMenuMapping[$sectionID])) {
        if (!isset($sectionMenuMapping[$sectionID][$groupName])) {
            $groupName = 'all';
        }

        return $sectionMenuMapping[$sectionID][$groupName];
    }

    return (object)[];
}

/**
 * @param stdClass $menuEntry
 * @return string
 */
function getConfigSectionPath(stdClass $menuEntry): string
{
    return $menuEntry->path ?? '';
}

/**
 * @param stdClass $menuEntry
 * @return string
 */
function getConfigSectionUrl(stdClass $menuEntry): string
{
    return $menuEntry->url ?? '';
}

/**
 * @param stdClass $menuEntry
 * @return bool
 */
function isConfigSectionSpecialSetting(stdClass $menuEntry): bool
{
    return $menuEntry->specialSetting ?? false;
}

/**
 * @param stdClass $menuEntry
 * @return string
 */
function getConfigSectionAnchor(stdClass $menuEntry): string
{
    return $menuEntry->settingsAnchor ?? '';
}

/**
 * @param array $config
 * @return array
 */
function sortiereEinstellungen(array $config): array
{
    if (count($config) === 0) {
        return [];
    }
    $sectionIDs = [];
    $sprt       = [];
    $tmpConf    = [];
    $sections   = [];
    foreach ($config as $conf) {
        if (isset($conf->kEinstellungenSektion) && $conf->cConf !== 'N') {
            $headline = holeEinstellungHeadline($conf->nSort, $conf->kEinstellungenSektion);
            if (isset($headline->kEinstellungenSektion) && !isset($sections[$headline->cWertName])) {
                $sections[$headline->cWertName] = true;
                $tmpConf[]                      = $headline;
            }
            $tmpConf[] = $conf;
        }
    }
    foreach ($tmpConf as $key => $value) {
        $sectionIDs[$key] = $value->kEinstellungenSektion;
        $sprt[$key]       = $value->nSort;
    }
    array_multisort($sectionIDs, SORT_ASC, $sprt, SORT_ASC, $tmpConf);

    return $tmpConf;
}

/**
 * settings page is separated but has same config group as parent config page, filter these settings
 *
 * @param array $confData
 * @param string $filter
 * @return array
 */
function filteredConfData(array $confData, string $filter): array
{
    $keys = [
        'configgroup_5_product_question'  => [
            'configgroup_5_product_question',
            'artikeldetails_fragezumprodukt_anzeigen',
            'artikeldetails_fragezumprodukt_email',
            'produktfrage_abfragen_anrede',
            'produktfrage_abfragen_vorname',
            'produktfrage_abfragen_nachname',
            'produktfrage_abfragen_firma',
            'produktfrage_abfragen_tel',
            'produktfrage_abfragen_fax',
            'produktfrage_abfragen_mobil',
            'produktfrage_kopiekunde',
            'produktfrage_sperre_minuten',
            'produktfrage_abfragen_captcha'
        ],
        'configgroup_5_product_available' => [
            'configgroup_5_product_available',
            'benachrichtigung_nutzen',
            'benachrichtigung_abfragen_vorname',
            'benachrichtigung_abfragen_nachname',
            'benachrichtigung_sperre_minuten',
            'benachrichtigung_abfragen_captcha',
            'benachrichtigung_min_lagernd'
        ]
    ];
    if (!extension_loaded('soap')) {
        $keys['configgroup_6_vat_id'] = [
            'shop_ustid_bzstpruefung',
            'shop_ustid_force_remote_check'
        ];
    }

    if ($filter !== '' && isset($keys[$filter])) {
        $keysToFilter = $keys[$filter];

        return filter($confData, static function ($e) use ($keysToFilter) {
            return in_array($e->cWertName, $keysToFilter, true);
        });
    }
    $keysToFilter = flatten($keys);

    return filter($confData, static function ($e) use ($keysToFilter) {
        return !in_array($e->cWertName, $keysToFilter, true);
    });
}

/**
 *  settings page is separated but has same config group as parent config page, get separate description
 *
 * @param int $sectionID
 * @return string
 */
function filteredConfDescription(int $sectionID): string
{
    switch (Request::verifyGPDataString('group')) {
        case 'configgroup_5_product_question':
            $desc = __('prefDesc5ProductQuestion');
            break;
        default:
            $desc = __('prefDesc' . $sectionID);
            break;
    }

    return $desc;
}
