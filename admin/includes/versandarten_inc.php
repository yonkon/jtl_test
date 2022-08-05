<?php

use JTL\Alert\Alert;
use JTL\Checkout\ShippingSurcharge;
use JTL\Checkout\ShippingSurchargeArea;
use JTL\Checkout\Versandart;
use JTL\Checkout\ZipValidator;
use JTL\Helpers\Text;
use JTL\Language\LanguageHelper;
use JTL\Language\LanguageModel;
use JTL\Shop;
use JTL\Smarty\ContextType;
use JTL\Smarty\JTLSmarty;

/**
 * @param float|string $price
 * @param float|string $taxRate
 * @return float
 */
function berechneVersandpreisBrutto($price, $taxRate)
{
    return $price > 0
        ? round((float)($price * ((100 + $taxRate) / 100)), 2)
        : 0.0;
}

/**
 * @param float|string $price
 * @param float|string $taxRate
 * @return float
 */
function berechneVersandpreisNetto($price, $taxRate)
{
    return $price > 0
        ? round($price * ((100 / (100 + $taxRate)) * 100) / 100, 2)
        : 0.0;
}

/**
 * @param array  $objects
 * @param string $key
 * @return array
 */
function reorganizeObjectArray($objects, $key): array
{
    $res = [];
    if (is_array($objects)) {
        foreach ($objects as $obj) {
            $arr  = get_object_vars($obj);
            $keys = array_keys($arr);
            if (in_array($key, $keys)) {
                $res[$obj->$key]           = new stdClass();
                $res[$obj->$key]->checked  = 'checked';
                $res[$obj->$key]->selected = 'selected';
                foreach ($keys as $k) {
                    if ($key != $k) {
                        $res[$obj->$key]->$k = $obj->$k;
                    }
                }
            }
        }
    }

    return $res;
}

/**
 * @param array $arr
 * @return array
 */
function P($arr): array
{
    $newArr = [];
    if (is_array($arr)) {
        foreach ($arr as $ele) {
            $newArr = bauePot($newArr, $ele);
        }
    }

    return $newArr;
}

/**
 * @param array  $arr
 * @param object $key
 * @return array
 */
function bauePot($arr, $key): array
{
    foreach ($arr as $val) {
        $obj                 = new stdClass();
        $obj->kVersandklasse = $val->kVersandklasse . '-' . $key->kVersandklasse;
        $obj->cName          = $val->cName . ', ' . $key->cName;
        $arr[]               = $obj;
    }
    $arr[] = $key;

    return $arr;
}

/**
 * @param string $shippingClasses
 * @return array
 */
function gibGesetzteVersandklassen(string $shippingClasses): array
{
    if (trim($shippingClasses) === '-1') {
        return ['alle' => true];
    }
    $gesetzteVK = [];
    $uniqueIDs  = [];
    $classes    = explode(' ', trim($shippingClasses));
    // $cVersandklassen is a string like "1 3-4 5-6-7 6-8 7-8 3-7 3-8 5-6 5-7"
    foreach ($classes as $idString) {
        // we want the single kVersandklasse IDs to reduce the possible amount of combinations
        foreach (explode('-', $idString) as $kVersandklasse) {
            $uniqueIDs[] = (int)$kVersandklasse;
        }
    }
    $items = P(Shop::Container()->getDB()->getObjects(
        'SELECT * 
            FROM tversandklasse
            WHERE kVersandklasse IN (' . implode(',', $uniqueIDs) . ')  
            ORDER BY kVersandklasse'
    ));
    foreach ($items as $vk) {
        $gesetzteVK[$vk->kVersandklasse] = in_array($vk->kVersandklasse, $classes, true);
    }

    return $gesetzteVK;
}

/**
 * @param string $shippingClasses
 * @return array
 */
function gibGesetzteVersandklassenUebersicht($shippingClasses)
{
    if (trim($shippingClasses) === '-1') {
        return ['Alle'];
    }
    $active    = [];
    $uniqueIDs = [];
    $classes   = explode(' ', trim($shippingClasses));
    // $cVersandklassen is a string like "1 3-4 5-6-7 6-8 7-8 3-7 3-8 5-6 5-7"
    foreach ($classes as $idString) {
        // we want the single kVersandklasse IDs to reduce the possible amount of combinations
        foreach (explode('-', $idString) as $kVersandklasse) {
            $uniqueIDs[] = (int)$kVersandklasse;
        }
    }
    $items = P(Shop::Container()->getDB()->getObjects(
        'SELECT * 
            FROM tversandklasse 
            WHERE kVersandklasse IN (' . implode(',', $uniqueIDs) . ')
            ORDER BY kVersandklasse'
    ));
    foreach ($items as $item) {
        if (in_array($item->kVersandklasse, $classes, true)) {
            $active[] = $item->cName;
        }
    }

    return $active;
}

/**
 * @param string $customerGroupsString
 * @return array
 */
function gibGesetzteKundengruppen(string $customerGroupsString): array
{
    $activeGroups = [];
    $groups       = Text::parseSSKint($customerGroupsString);
    $groupData    = Shop::Container()->getDB()->getObjects(
        'SELECT kKundengruppe
            FROM tkundengruppe
            ORDER BY kKundengruppe'
    );
    foreach ($groupData as $group) {
        $id                = (int)$group->kKundengruppe;
        $activeGroups[$id] = in_array($id, $groups, true);
    }
    $activeGroups['alle'] = $customerGroupsString === '-1';

    return $activeGroups;
}

/**
 * @param int             $shippingMethodID
 * @param LanguageModel[] $languages
 * @return array
 */
function getShippingLanguage(int $shippingMethodID, array $languages): array
{
    $localized        = [];
    $localizedMethods = Shop::Container()->getDB()->selectAll(
        'tversandartsprache',
        'kVersandart',
        $shippingMethodID
    );
    foreach ($languages as $language) {
        $localized[$language->getCode()] = new stdClass();
    }
    foreach ($localizedMethods as $localizedMethod) {
        if (isset($localizedMethod->kVersandart) && $localizedMethod->kVersandart > 0) {
            $localized[$localizedMethod->cISOSprache] = $localizedMethod;
        }
    }

    return $localized;
}

/**
 * @param int $feeID
 * @return array
 */
function getZuschlagNames(int $feeID): array
{
    $names = [];
    if (!$feeID) {
        return $names;
    }
    $localized = Shop::Container()->getDB()->selectAll(
        'tversandzuschlagsprache',
        'kVersandzuschlag',
        $feeID
    );
    foreach ($localized as $name) {
        $names[$name->cISOSprache] = $name->cName;
    }

    return $names;
}

/**
 * @param string $query
 * @return array
 */
function getShippingByName(string $query): array
{
    $results = [];
    $db      = Shop::Container()->getDB();
    foreach (explode(',', $query) as $search) {
        $search = trim($search);
        if (mb_strlen($search) > 2) {
            $hits = $db->getObjects(
                'SELECT va.kVersandart, va.cName
                    FROM tversandart AS va
                    LEFT JOIN tversandartsprache AS vs 
                        ON vs.kVersandart = va.kVersandart
                        AND vs.cName LIKE :search
                    WHERE va.cName LIKE :search
                    OR vs.cName LIKE :search',
                ['search' => '%' . $search . '%']
            );
            foreach ($hits as $item) {
                $item->kVersandart           = (int)$item->kVersandart;
                $results[$item->kVersandart] = $item;
            }
        }
    }

    return $results;
}

/**
 * @param array $shipClasses
 * @param int   $length
 * @return array
 */
function getCombinations(array $shipClasses, int $length): array
{
    $baselen = count($shipClasses);
    if ($baselen === 0) {
        return [];
    }
    if ($length === 1) {
        $return = [];
        foreach ($shipClasses as $b) {
            $return[] = [$b];
        }

        return $return;
    }

    // get one level lower combinations
    $oneLevelLower = getCombinations($shipClasses, $length - 1);
    // for every one level lower combinations add one element to them
    // that the last element of a combination is preceeded by the element
    // which follows it in base array if there is none, does not add
    $newCombs = [];
    foreach ($oneLevelLower as $oll) {
        $lastEl = $oll[$length - 2];
        $found  = false;
        foreach ($shipClasses as $key => $b) {
            if ($b === $lastEl) {
                $found = true;
                continue;
                // last element found
            }
            if ($found === true && $key < $baselen) {
                // add to combinations with last element
                $tmp              = $oll;
                $newCombination   = array_slice($tmp, 0);
                $newCombination[] = $b;
                $newCombs[]       = array_slice($newCombination, 0);
            }
        }
    }

    return $newCombs;
}

/**
 * @return array|int -1 if too many shipping classes exist
 */
function getMissingShippingClassCombi()
{
    $shippingClasses         = Shop::Container()->getDB()->selectAll('tversandklasse', [], [], 'kVersandklasse');
    $combinationsInShippings = Shop::Container()->getDB()->selectAll('tversandart', [], [], 'cVersandklassen');
    $shipClasses             = [];
    $combinationInUse        = [];

    foreach ($shippingClasses as $sc) {
        $shipClasses[] = $sc->kVersandklasse;
    }

    foreach ($combinationsInShippings as $com) {
        foreach (explode(' ', trim($com->cVersandklassen)) as $class) {
            $combinationInUse[] = trim($class);
        }
    }

    // if a shipping method is valid for all classes return
    if (in_array('-1', $combinationInUse, false)) {
        return [];
    }

    $len = count($shipClasses);
    if ($len > SHIPPING_CLASS_MAX_VALIDATION_COUNT) {
        return -1;
    }

    $possibleShippingClassCombinations = [];
    for ($i = 1; $i <= $len; $i++) {
        $result = getCombinations($shipClasses, $i);
        foreach ($result as $c) {
            $possibleShippingClassCombinations[] = implode('-', $c);
        }
    }
    $res = array_diff($possibleShippingClassCombinations, $combinationInUse);
    foreach ($res as &$mscc) {
        $mscc = gibGesetzteVersandklassenUebersicht($mscc)[0];
    }

    return $res;
}

/**
 * @param array $data
 * @return stdClass
 * @throws SmartyException
 */
function saveShippingSurcharge(array $data): stdClass
{
    Shop::Container()->getGetText()->loadAdminLocale('pages/versandarten');

    $alertHelper = Shop::Container()->getAlertService();
    $smarty      = JTLSmarty::getInstance(false, ContextType::BACKEND);
    $post        = [];
    foreach ($data as $item) {
        $post[$item['name']] = $item['value'];
    }
    $surcharge = (float)str_replace(',', '.', $post['fZuschlag']);

    if (!$post['cName']) {
        $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorListNameMissing'), 'errorListNameMissing');
    }
    if (empty($surcharge)) {
        $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorListPriceMissing'), 'errorListPriceMissing');
    }
    if (!$alertHelper->alertTypeExists(Alert::TYPE_ERROR)) {
        if (empty($post['kVersandzuschlag'])) {
            $surchargeTMP = (new ShippingSurcharge())
                ->setISO($post['cISO'])
                ->setSurcharge($surcharge)
                ->setShippingMethod($post['kVersandart'])
                ->setTitle($post['cName']);
        } else {
            $surchargeTMP = (new ShippingSurcharge((int)$post['kVersandzuschlag']))
                ->setTitle($post['cName'])
                ->setSurcharge($surcharge);
        }
        foreach (Sprache::getAllLanguages(0, true) as $lang) {
            $idx = 'cName_' . $lang->getCode();
            if (isset($post[$idx])) {
                $surchargeTMP->setName($post[$idx] ?: $post['cName'], $lang->getId());
            }
        }
        $surchargeTMP->save();
        $surchargeTMP = new ShippingSurcharge($surchargeTMP->getID());
    }
    $message = $smarty->assign('alertList', $alertHelper)
        ->fetch('snippets/alert_list.tpl');

    Shop::Container()->getCache()->flushTags([CACHING_GROUP_OBJECT, CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]);

    return (object)[
        'title'          => isset($surchargeTMP) ? $surchargeTMP->getTitle() : '',
        'priceLocalized' => isset($surchargeTMP) ? $surchargeTMP->getPriceLocalized() : '',
        'id'             => isset($surchargeTMP) ? $surchargeTMP->getID() : '',
        'reload'         => empty($post['kVersandzuschlag']),
        'message'        => $message,
        'error'          => $alertHelper->alertTypeExists(Alert::TYPE_ERROR)
    ];
}

/**
 * @param int $surchargeID
 * @return stdClass
 */
function deleteShippingSurcharge(int $surchargeID): stdClass
{
    Shop::Container()->getDB()->queryPrepared(
        'DELETE tversandzuschlag, tversandzuschlagsprache, tversandzuschlagplz
            FROM tversandzuschlag
            LEFT JOIN tversandzuschlagsprache USING(kVersandzuschlag)
            LEFT JOIN tversandzuschlagplz USING(kVersandzuschlag)
            WHERE tversandzuschlag.kVersandzuschlag = :surchargeID',
        ['surchargeID' => $surchargeID]
    );
    Shop::Container()->getCache()->flushTags([CACHING_GROUP_OBJECT, CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]);

    return (object)['surchargeID' => $surchargeID];
}

/**
 * @param int    $surchargeID
 * @param string $ZIP
 * @return stdClass
 */
function deleteShippingSurchargeZIP(int $surchargeID, string $ZIP): stdClass
{
    $partsZIP = explode('-', $ZIP);
    if (count($partsZIP) === 1) {
        Shop::Container()->getDB()->queryPrepared(
            'DELETE 
            FROM tversandzuschlagplz
            WHERE kVersandzuschlag = :surchargeID
              AND cPLZ = :ZIP',
            [
                'surchargeID' => $surchargeID,
                'ZIP'         => $partsZIP[0]
            ]
        );
    } elseif (count($partsZIP) === 2) {
        Shop::Container()->getDB()->queryPrepared(
            'DELETE 
            FROM tversandzuschlagplz
            WHERE kVersandzuschlag = :surchargeID
              AND cPLZab = :ZIPFrom
              AND cPLZbis = :ZIPTo',
            [
                'surchargeID' => $surchargeID,
                'ZIPFrom'     => $partsZIP[0],
                'ZIPTo'       => $partsZIP[1]
            ]
        );
    }
    Shop::Container()->getCache()->flushTags([CACHING_GROUP_OBJECT, CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]);

    return (object)['surchargeID' => $surchargeID, 'ZIP' => $ZIP];
}

/**
 * @param array $data
 * @return stdClass
 * @throws SmartyException
 */
function createShippingSurchargeZIP(array $data): stdClass
{
    Shop::Container()->getGetText()->loadAdminLocale('pages/versandarten');

    $post = [];
    foreach ($data as $item) {
        $post[$item['name']] = $item['value'];
    }
    $alertHelper    = Shop::Container()->getAlertService();
    $db             = Shop::Container()->getDB();
    $smarty         = JTLSmarty::getInstance(false, ContextType::BACKEND);
    $surcharge      = new ShippingSurcharge((int)$post['kVersandzuschlag']);
    $shippingMethod = new Versandart($surcharge->getShippingMethod());
    $zipValidator   = new ZipValidator($surcharge->getISO());
    $surchargeZip   = new stdClass();

    $surchargeZip->kVersandzuschlag = $surcharge->getID();
    $surchargeZip->cPLZ             = '';
    $surchargeZip->cPLZAb           = '';
    $surchargeZip->cPLZBis          = '';
    $area                           = null;

    if (!empty($post['cPLZ'])) {
        $surchargeZip->cPLZ = $zipValidator->validateZip($post['cPLZ']);
    } elseif (!empty($post['cPLZAb']) && !empty($post['cPLZBis'])) {
        $area = new ShippingSurchargeArea($post['cPLZAb'], $post['cPLZBis']);
        if ($area->getZIPFrom() === $area->getZIPTo()) {
            $surchargeZip->cPLZ = $zipValidator->validateZip($area->getZIPFrom());
        } else {
            $surchargeZip->cPLZAb  = $zipValidator->validateZip($area->getZIPFrom());
            $surchargeZip->cPLZBis = $zipValidator->validateZip($area->getZIPTo());
        }
    }

    $zipMatchSurcharge = $shippingMethod->getShippingSurchargesForCountry($surcharge->getISO())
        ->first(static function (ShippingSurcharge $surchargeTMP) use ($surchargeZip) {
            return ($surchargeTMP->hasZIPCode($surchargeZip->cPLZ)
                || $surchargeTMP->hasZIPCode($surchargeZip->cPLZAb)
                || $surchargeTMP->hasZIPCode($surchargeZip->cPLZBis)
                || $surchargeTMP->areaOverlapsWithZIPCode($surchargeZip->cPLZAb, $surchargeZip->cPLZBis)
            );
        });
    if ($area !== null && !$area->lettersMatch()) {
        $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorZIPsDoNotMatch'), 'errorZIPsDoNotMatch');
    } elseif (empty($surchargeZip->cPLZ) && empty($surchargeZip->cPLZAb)) {
        $error = $zipValidator->getError();
        if ($error !== '') {
            $alertHelper->addAlert(Alert::TYPE_ERROR, $error, 'errorZIPValidator');
        } else {
            $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorZIPMissing'), 'errorZIPMissing');
        }
    } elseif ($zipMatchSurcharge !== null) {
        $alertHelper->addAlert(
            Alert::TYPE_ERROR,
            sprintf(
                isset($surchargeZip->cPLZ) ? __('errorZIPOverlap') : __('errorZIPAreaOverlap'),
                $surchargeZip->cPLZ ?? $surchargeZip->cPLZAb . ' - ' . $surchargeZip->cPLZBis,
                $zipMatchSurcharge->getTitle()
            ),
            'errorZIPOverlap'
        );
    } elseif ($db->insert('tversandzuschlagplz', $surchargeZip)) {
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successZIPAdd'), 'successZIPAdd');
    }
    Shop::Container()->getCache()->flushTags([CACHING_GROUP_OBJECT, CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]);

    $message = $smarty->assign('alertList', $alertHelper)
        ->fetch('snippets/alert_list.tpl');
    $badges  = $smarty->assign('surcharge', new ShippingSurcharge($surcharge->getID()))
        ->fetch('snippets/zuschlagliste_plz_badges.tpl');

    return (object)['message' => $message, 'badges' => $badges, 'surchargeID' => $surcharge->getID()];
}

/**
 * @param int|null $shippingTypeID
 * @return array|mixed
 */
function getShippingTypes(int $shippingTypeID = null)
{
    if ($shippingTypeID !== null) {
        $shippingTypes = Shop::Container()->getDB()->getCollection(
            'SELECT *
                FROM tversandberechnung
                WHERE kVersandberechnung = :shippingTypeID
                ORDER BY cName',
            ['shippingTypeID' => $shippingTypeID]
        );
    } else {
        $shippingTypes = Shop::Container()->getDB()->getCollection(
            'SELECT *
                FROM tversandberechnung
                ORDER BY cName'
        );
    }
    $shippingTypes->each(static function ($e) {
        $e->kVersandberechnung = (int)$e->kVersandberechnung;
        $e->cName              = __('shippingType_' . $e->cModulId);
    });

    return $shippingTypeID === null ? $shippingTypes->toArray() : $shippingTypes->first();
}

/**
 * @param int $id
 * @return stdClass
 * @throws SmartyException
 */
function getShippingSurcharge(int $id): stdClass
{
    Shop::Container()->getGetText()->loadAdminLocale('pages/versandarten');

    $smarty       = JTLSmarty::getInstance(false, ContextType::BACKEND);
    $result       = new stdClass();
    $result->body = $smarty->assign('sprachen', LanguageHelper::getAllLanguages(0, true))
        ->assign('surchargeNew', new ShippingSurcharge($id))
        ->assign('surchargeID', $id)
        ->fetch('snippets/zuschlagliste_form.tpl');

    return $result;
}
