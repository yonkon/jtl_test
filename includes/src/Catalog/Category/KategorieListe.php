<?php

namespace JTL\Catalog\Category;

use JTL\DB\DbInterface;
use JTL\Helpers\Category;
use JTL\Language\LanguageHelper;
use JTL\Session\Frontend;
use JTL\Shop;

/**
 * Class KategorieListe
 * @package JTL\Catalog\Category
 */
class KategorieListe
{
    /**
     * @var Kategorie[]
     */
    public $elemente;

    /**
     * @var bool
     */
    public static $wasModified = false;

    /**
     * temporary array to store list of all categories
     * used since getCategoryList() is called very often
     * and may create overhead on unserialize() in the caching class
     *
     * @var array
     */
    private static $allCats = [];

    /**
     * Holt die ersten 3 Ebenen von Kategorien, jeweils nach Name sortiert
     *
     * @param int $levels
     * @param int $customerGroupID
     * @param int $languageID
     * @return array
     * @deprecated since 5.0.0
     */
    public function holKategorienAufEinenBlick(int $levels = 2, int $customerGroupID = 0, int $languageID = 0): array
    {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        $this->elemente = [];
        if (!Frontend::getCustomerGroup()->mayViewCategories()) {
            return $this->elemente;
        }
        $customerGroupID = $customerGroupID ?: Frontend::getCustomerGroup()->getID();
        $languageID      = $languageID ?: Shop::getLanguageID();
        $levels          = \min($levels, 3);
        foreach ($this->getChildCategories(0, $customerGroupID, $languageID) as $level1item) {
            $cat1           = $level1item;
            $cat1->children = [];
            if ($levels > 1) {
                // 2nd level
                $level2items = $this->getChildCategories($cat1->kKategorie, $customerGroupID, $languageID);
                foreach ($level2items as $level2item) {
                    $cat2           = $level2item;
                    $cat2->children = [];
                    if ($levels > 2) {
                        // 3rd level
                        $cat2->children = $this->getChildCategories(
                            $cat2->kKategorie,
                            $customerGroupID,
                            $languageID
                        );
                    }
                    $cat1->children[] = $cat2;
                }
            }
            $this->elemente[] = $cat1;
        }

        return $this->elemente;
    }

    /**
     * Holt Stamm einer Kategorie
     *
     * @param Kategorie $category
     * @param int       $customerGroupID
     * @param int       $languageID
     * @return array
     * @deprecated since 5.0.0
     */
    public function getUnterkategorien($category, int $customerGroupID = 0, int $languageID = 0): array
    {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        $this->elemente = [];
        if (!Frontend::getCustomerGroup()->mayViewCategories()) {
            return $this->elemente;
        }
        if (!$customerGroupID) {
            $customerGroupID = Frontend::getCustomerGroup()->getID();
        }
        if (!$languageID) {
            $languageID = Shop::getLanguageID();
        }
        $searchIn   = [];
        $searchIn[] = $category;
        while (\count($searchIn) > 0) {
            $current = \array_pop($searchIn);
            if (!empty($current->kKategorie)) {
                $this->elemente[] = $current;
                foreach ($this->getChildCategories($current->kKategorie, $customerGroupID, $languageID) as $item) {
                    $searchIn[] = $item;
                }
            }
        }

        return $this->elemente;
    }

    /**
     * @param int $categoryID
     * @param int $customerGroupID
     * @param int $languageID
     * @return array
     * @deprecated since 5.0.0
     */
    public function holUnterkategorien(int $categoryID, int $customerGroupID, int $languageID): array
    {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        return $this->getChildCategories($categoryID, $customerGroupID, $languageID);
    }

    /**
     * Holt UnterKategorien für die spezifizierte kKategorie, jeweils nach nSort, Name sortiert
     *
     * @param int $categoryID - Kategorieebene. 0 -> rootEbene
     * @param int $customerGroupID
     * @param int $languageID
     * @return array
     */
    public function getAllCategoriesOnLevel(int $categoryID, int $customerGroupID = 0, int $languageID = 0): array
    {
        $this->elemente = [];
        if (!Frontend::getCustomerGroup()->mayViewCategories()) {
            return $this->elemente;
        }
        $customerGroupID = $customerGroupID ?: Frontend::getCustomerGroup()->getID();
        $languageID      = $languageID ?: Shop::getLanguageID();
        $conf            = Shop::getSettings([\CONF_NAVIGATIONSFILTER])['navigationsfilter'];
        $showLevel2      = $conf['unterkategorien_lvl2_anzeigen'] ?? 'N';
        if ($categoryID > 0 && \count(self::$allCats) === 0) {
            $this->getAllCategoriesOnLevel(0, $customerGroupID, $languageID);
        }
        foreach ($this->getChildCategories($categoryID, $customerGroupID, $languageID) as $category) {
            $category->bAktiv          = (Shop::$kKategorie > 0 && (int)$category->kKategorie === (int)Shop::$kKategorie);
            $category->Unterkategorien = [];
            if ($showLevel2 === 'Y') {
                $category->Unterkategorien = $this->getChildCategories(
                    $category->kKategorie,
                    $customerGroupID,
                    $languageID
                );
            }
            $this->elemente[] = $category;
        }
        if ($categoryID === 0 && self::$wasModified === true) {
            $cacheID = \CACHING_GROUP_CATEGORY . '_list_' . $customerGroupID . '_' . $languageID;
            Shop::Container()->getCache()->set(
                $cacheID,
                self::$allCats[$cacheID],
                [\CACHING_GROUP_CATEGORY]
            );
        }

        return $this->elemente;
    }

    /**
     * @param int $customerGroupID
     * @param int $languageID
     * @return array
     */
    public static function getCategoryList(int $customerGroupID, int $languageID): array
    {
        $cacheID = \CACHING_GROUP_CATEGORY . '_list_' . $customerGroupID . '_' . $languageID;
        if (isset(self::$allCats[$cacheID])) {
            return self::$allCats[$cacheID];
        }
        if (($allCategories = Shop::Container()->getCache()->get($cacheID)) !== false) {
            self::$allCats[$cacheID] = $allCategories;

            return $allCategories;
        }

        return [
            'oKategorie_arr'                   => [],
            'kKategorieVonUnterkategorien_arr' => [],
            'ks'                               => []
        ];
    }

    /**
     * @param array $categoryList
     * @param int   $customerGroupID
     * @param int   $languageID
     */
    public static function setCategoryList($categoryList, int $customerGroupID, int $languageID): void
    {
        $cacheID                 = \CACHING_GROUP_CATEGORY . '_list_' . $customerGroupID . '_' . $languageID;
        self::$allCats[$cacheID] = $categoryList;
    }

    /**
     * Holt alle augeklappten Kategorien für eine gewählte Kategorie, jeweils nach Name sortiert
     *
     * @param Kategorie $currentCategory
     * @param int       $customerGroupID
     * @param int       $languageID
     * @return Kategorie[]
     */
    public function getOpenCategories(Kategorie $currentCategory, int $customerGroupID = 0, int $languageID = 0): array
    {
        $this->elemente = [];
        if (empty($currentCategory->kKategorie) || !Frontend::getCustomerGroup()->mayViewCategories()) {
            return $this->elemente;
        }
        $this->elemente[] = $currentCategory;
        $currentParent    = $currentCategory->kOberKategorie;
        $customerGroupID  = $customerGroupID ?: Frontend::getCustomerGroup()->getID();
        $languageID       = $languageID ?: Shop::getLanguageID();
        $allCategories    = static::getCategoryList($customerGroupID, $languageID);
        while ($currentParent > 0) {
            $category         = $allCategories['oKategorie_arr'][$currentParent]
                ?? new Kategorie($currentParent, $languageID, $customerGroupID);
            $this->elemente[] = $category;
            $currentParent    = $category->kOberKategorie;
        }

        return $this->elemente;
    }

    /**
     * @param int $categoryID
     * @param int $customerGroupID
     * @param int $languageID
     * @return Kategorie[]
     */
    public function getChildCategories(int $categoryID, int $customerGroupID, int $languageID): array
    {
        if (!Frontend::getCustomerGroup()->mayViewCategories()) {
            return [];
        }
        $categories      = [];
        $customerGroupID = $customerGroupID ?: Frontend::getCustomerGroup()->getID();
        $languageID      = $languageID ?: Shop::getLanguageID();
        $categoryList    = self::getCategoryList($customerGroupID, $languageID);
        $subCategories   = $categoryList['kKategorieVonUnterkategorien_arr'][$categoryID] ?? null;
        if ($subCategories !== null && \is_array($subCategories)) {
            foreach ($subCategories as $subCatID) {
                $categories[$subCatID] = $categoryList['oKategorie_arr'][$subCatID]
                    ?? new Kategorie($subCatID, $languageID, $customerGroupID);
            }

            return $categories;
        }

        if ($categoryID > 0) {
            self::$wasModified = true;
        }
        // ist nicht im cache, muss holen
        $db                                                            = Shop::Container()->getDB();
        $defaultLanguageActive                                         = LanguageHelper::isDefaultLanguageActive();
        $orderByName                                                   = $defaultLanguageActive ? '' : 'tkategoriesprache.cName, ';
        $categories                                                    = $db->getObjects(
            'SELECT tkategorie.kKategorie
                FROM tkategorie
                LEFT JOIN tkategoriesprache 
                    ON tkategoriesprache.kKategorie = tkategorie.kKategorie
                    AND tkategoriesprache.kSprache = :lid
                LEFT JOIN tkategoriesichtbarkeit 
                    ON tkategorie.kKategorie = tkategoriesichtbarkeit.kKategorie
                AND tkategoriesichtbarkeit.kKundengruppe = :cgid
                WHERE tkategoriesichtbarkeit.kKategorie IS NULL
                    AND tkategorie.kOberKategorie = :cid
                ORDER BY tkategorie.nSort, ' . $orderByName . 'tkategorie.cName',
            ['lid' => $languageID, 'cid' => $categoryID, 'cgid' => $customerGroupID]
        );
        $categoryList['kKategorieVonUnterkategorien_arr'][$categoryID] = [];
        foreach ($categories as $i => &$category) {
            $category = new Kategorie((int)$category->kKategorie, $languageID, $customerGroupID);
            if (!$this->nichtLeer($category->kKategorie, $customerGroupID)) {
                $categoryList['ks'][$category->kKategorie] = 2;
                unset($categories[$i]);
                continue;
            }
            // ks = ist kategorie leer 1 = nein, 2 = ja
            $categoryList['ks'][$category->kKategorie]                       = 1;
            $categoryList['kKategorieVonUnterkategorien_arr'][$categoryID][] = $category->kKategorie;
            $categoryList['oKategorie_arr'][$category->kKategorie]           = $category;
        }
        unset($category);
        $categories = \array_merge($categories);
        self::setCategoryList($categoryList, $customerGroupID, $languageID);

        return $categories;
    }

    /**
     * @param int $categoryID
     * @param int $customerGroupID
     * @return bool
     */
    public function nichtLeer(int $categoryID, int $customerGroupID): bool
    {
        $conf = Shop::getSettings([\CONF_GLOBAL])['global'];
        if ((int)$conf['kategorien_anzeigefilter'] === \EINSTELLUNGEN_KATEGORIEANZEIGEFILTER_ALLE) {
            return true;
        }
        $languageID = (int)LanguageHelper::getDefaultLanguage()->kSprache;
        if ((int)$conf['kategorien_anzeigefilter'] === \EINSTELLUNGEN_KATEGORIEANZEIGEFILTER_NICHTLEERE) {
            $categoryList = self::getCategoryList($customerGroupID, $languageID);
            if (isset($categoryList['ks'][$categoryID])) {
                if ($categoryList['ks'][$categoryID] === 1) {
                    return true;
                }
                if ($categoryList['ks'][$categoryID] === 2) {
                    return false;
                }
            }
            $db            = Shop::Container()->getDB();
            $categoryIDs   = [];
            $categoryIDs[] = $categoryID;
            while (\count($categoryIDs) > 0) {
                $category = \array_pop($categoryIDs);
                if ($this->hasProducts($languageID, $category, $customerGroupID)) {
                    $categoryList['ks'][$categoryID] = 1;
                    self::setCategoryList($categoryList, $customerGroupID, $languageID);

                    return true;
                }
                $catData = $db->getObjects(
                    'SELECT tkategorie.kKategorie
                        FROM tkategorie
                        LEFT JOIN tkategoriesichtbarkeit 
                            ON tkategorie.kKategorie = tkategoriesichtbarkeit.kKategorie
                            AND tkategoriesichtbarkeit.kKundengruppe = :cgid
                        WHERE tkategoriesichtbarkeit.kKategorie IS NULL
                            AND tkategorie.kOberKategorie = :pcid
                            AND tkategorie.kKategorie != :cid',
                    ['cid' => $categoryID, 'pcid' => $category, 'cgid' => $customerGroupID]
                );
                foreach ($catData as $obj) {
                    $categoryIDs[] = (int)$obj->kKategorie;
                }
            }
            $categoryList['ks'][$categoryID] = 2;
            self::setCategoryList($categoryList, $customerGroupID, $languageID);

            return false;
        }
        $categoryList['ks'][$categoryID] = 1;
        self::setCategoryList($categoryList, $customerGroupID, $languageID);

        return true;
    }

    /**
     * @param int $languageID
     * @param int $categoryID
     * @param int $customerGroupID
     * @return bool
     */
    private function hasProducts(int $languageID, int $categoryID, int $customerGroupID): bool
    {
        return Category::getInstance($languageID, $customerGroupID)->categoryHasProducts($categoryID);
    }
}
