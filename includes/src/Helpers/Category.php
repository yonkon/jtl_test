<?php

namespace JTL\Helpers;

use JTL\Catalog\Category\Kategorie;
use JTL\Catalog\Category\MenuItem;
use JTL\DB\DbInterface;
use JTL\Language\LanguageHelper;
use JTL\Session\Frontend;
use JTL\Shop;
use stdClass;

/**
 * Class Category
 * @package JTL\Helpers
 */
class Category
{
    /**
     * @var Category
     */
    private static $instance;

    /**
     * @var int
     */
    private static $languageID;

    /**
     * @var int
     */
    private static $customerGroupID;

    /**
     * @var int
     */
    private static $depth;

    /**
     * @var string
     */
    private static $cacheID;

    /**
     * @var array
     */
    private static $config;

    /**
     * @var array|null
     */
    private static $fullCategories;

    /**
     * @var int[]|null
     */
    private static $lostCategories;

    /**
     * @var bool
     */
    private static $limitReached = false;

    /**
     * @var DbInterface
     */
    private static $db;

    /**
     * @var array|null
     */
    private static $prodCatAssociations;

    /**
     * Category constructor.
     */
    protected function __construct()
    {
        self::$instance = $this;
    }

    /**
     * @param int $languageID
     * @param int $customerGroupID
     * @return Category
     */
    public static function getInstance(int $languageID = 0, int $customerGroupID = 0): self
    {
        $languageID      = $languageID === 0
            ? Shop::getLanguageID()
            : $languageID;
        $customerGroupID = $customerGroupID === 0
            ? Frontend::getCustomerGroup()->getID()
            : $customerGroupID;
        $config          = Shop::getSettings([\CONF_GLOBAL, \CONF_TEMPLATE, \CONF_NAVIGATIONSFILTER]);
        if (self::$instance !== null && self::$languageID !== $languageID) {
            // reset cached categories when language or depth was changed
            self::$fullCategories = null;
            unset($_SESSION['oKategorie_arr_new']);
        }
        self::$cacheID         = 'allcategories_' . $customerGroupID .
            '_' . $languageID .
            '_' . $config['global']['kategorien_anzeigefilter'];
        self::$languageID      = $languageID;
        self::$customerGroupID = $customerGroupID;
        self::$config          = $config;
        self::$db              = Shop::Container()->getDB();

        return self::$instance ?? new self();
    }

    /**
     * @param int $categoryID
     * @return array|null
     */
    private function getCacheTree(int $categoryID): ?array
    {
        $cacheID = self::$cacheID . '_' . $categoryID;
        $cache   = Shop::Container()->getCache();
        $item    = $cache->get($cacheID);
        if ($item === false) {
            $item = $_SESSION['oKategorie_arr_new_' . $cacheID] ?? null;
        }

        if (\is_array($item)) {
            self::$limitReached = $item['limitReached'];
            self::$depth        = $item['depth'];

            return $item['tree'];
        }

        return null;
    }

    /**
     * @param int   $categoryID
     * @param array $tree
     */
    private function setCacheTree(int $categoryID, array $tree): void
    {
        $cacheID = self::$cacheID . '_' . $categoryID;
        $cache   = Shop::Container()->getCache();
        $item    = [
            'tree'         => $tree,
            'limitReached' => self::$limitReached,
            'depth'        => self::$depth,
        ];
        if ($cache->set($cacheID, $item, [\CACHING_GROUP_CATEGORY, 'jtl_category_tree']) === false) {
            $_SESSION['oKategorie_arr_new_' . $cacheID] = $item;
        }
    }

    /**
     * @param int $startCat
     * @param int $startLevel
     * @return array
     */
    public function combinedGetAll(int $startCat = 0, int $startLevel = 0): array
    {
        if ($startCat === 0 && self::$fullCategories !== null) {
            return self::$fullCategories;
        }

        if (($fullCats = $this->getCacheTree($startCat)) === null) {
            $filterEmpty         = (int)self::$config['global']['kategorien_anzeigefilter'] ===
                \EINSTELLUNGEN_KATEGORIEANZEIGEFILTER_NICHTLEERE;
            $functionAttributes  = [];
            $localizedAttributes = [];
            foreach ($this->getAttributes() as $catAttribute) {
                $catID = $catAttribute->kKategorie;
                $idx   = \mb_convert_case($catAttribute->cName, \MB_CASE_LOWER);
                if ($catAttribute->bIstFunktionsAttribut) {
                    $functionAttributes[$catID][$idx] = $catAttribute->cWert;
                } else {
                    $localizedAttributes[$catID][$idx] = $catAttribute;
                }
            }
            $prefix = Shop::getURL() . '/';
            $nodes  = $this->getNodes($startCat, $startLevel);
            foreach ($nodes as $cat) {
                $id = $cat->getID();
                $cat->setURL(URL::buildURL($cat, \URLART_KATEGORIE, true, $prefix));
                $cat->setFunctionalAttributes($functionAttributes[$id] ?? []);
                $cat->setAttributes($localizedAttributes[$id] ?? []);
                $cat->setShortName($cat->getAttribute(\ART_ATTRIBUT_SHORTNAME)->cWert ?? $cat->getName());
            }
            $fullCats = $this->buildTree($nodes, $startCat);
            $fullCats = $this->setOrphanedCategories($nodes, $fullCats);
            if ($filterEmpty) {
                $fullCats = $this->removeRelicts($this->filterEmpty($fullCats));
            }
            \executeHook(\HOOK_GET_ALL_CATEGORIES, ['categories' => &$fullCats]);
            $this->setCacheTree($startCat, $fullCats);
        }

        return $fullCats;
    }

    /**
     * @param int $startCat
     * @param int $startLevel
     * @return MenuItem[]
     */
    private function getNodes(int $startCat = 0, int $startLevel = 0): array
    {
        $queryParams        = [
            'langID'   => self::$languageID,
            'cgID'     => self::$customerGroupID,
            'startCat' => $startCat,
            'startLvl' => $startLevel,
        ];
        $filterEmpty        = (int)self::$config['global']['kategorien_anzeigefilter'] ===
            \EINSTELLUNGEN_KATEGORIEANZEIGEFILTER_NICHTLEERE;
        $stockFilter        = Shop::getProductFilter()->getFilterSQL()->getStockFilterSQL();
        $showCategoryImages = self::$config['template']['megamenu']['show_category_images'] ?? 'N';
        $extended           = !empty($stockFilter);
        $isDefaultLang      = LanguageHelper::isDefaultLanguageActive();
        $categoryCount      = (int)self::$db->getSingleObject('SELECT COUNT(*) AS cnt FROM tkategorie')->cnt;
        self::$limitReached = $categoryCount >= \CATEGORY_FULL_LOAD_LIMIT;
        self::$depth        = self::$limitReached ? \CATEGORY_FULL_LOAD_MAX_LEVEL : -1;
        $descriptionSelect  = ", '' AS cBeschreibung";
        $depthWhere         = self::$limitReached === true
            ? ' AND node.nLevel <= (:startLvl + ' . \CATEGORY_FULL_LOAD_MAX_LEVEL . ')'
            : '';
        $getDescription     = ($categoryCount < \CATEGORY_FULL_LOAD_LIMIT
            || // always get description if there aren't that many categories
            !(isset(self::$config['template']['megamenu']['show_maincategory_info'])
                // otherwise check template config
                && isset(self::$config['template']['megamenu']['show_categories'])
                && (self::$config['template']['megamenu']['show_categories'] === 'N'
                    || self::$config['template']['megamenu']['show_maincategory_info'] === 'N')));

        if ($getDescription === true) {
            $descriptionSelect = $isDefaultLang === true
                ? ', node.cBeschreibung' // no description needed if we don't show category info in mega menu
                : ', node.cBeschreibung, tkategoriesprache.cBeschreibung AS cBeschreibung_spr';
        }
        $imageSelect = ($categoryCount >= \CATEGORY_FULL_LOAD_LIMIT && $showCategoryImages === 'N')
            ? ", '' AS cPfad" // select empty path if we don't need category images for the mega menu
            : ', tkategoriepict.cPfad, atr.cWert As customImgName';
        $imageJoin   = ($categoryCount >= \CATEGORY_FULL_LOAD_LIMIT && $showCategoryImages === 'N')
            ? '' //the join is not needed if we don't select the category image path
            : ' LEFT JOIN tkategoriepict
                    ON tkategoriepict.kKategorie = node.kKategorie
                LEFT JOIN tkategorieattribut atr
                    ON atr.kKategorie = node.kKategorie
                    AND atr.cName = \'bildname\'';
        $nameSelect  = $isDefaultLang === true
                ? ', node.cName'
                : ', node.cName, tkategoriesprache.cName AS cName_spr';
        $langJoin    = $isDefaultLang === true
                ? ''
                : ' LEFT JOIN tkategoriesprache
                        ON tkategoriesprache.kKategorie = node.kKategorie
                            AND tkategoriesprache.kSprache = :langID ';
        $seoJoin     = " LEFT JOIN tseo
                        ON tseo.cKey = 'kKategorie'
                        AND tseo.kKey = node.kKategorie
                        AND tseo.kSprache = :langID ";
        if ($extended) {
            $countSelect    = ', COALESCE(s1.cnt, 0) cnt';
            $visibilityJoin = ' LEFT JOIN (
                SELECT tkategorieartikel.kKategorie, COUNT(tkategorieartikel.kArtikel) AS cnt
                FROM tkategorieartikel
                INNER JOIN tartikel
                    ON tkategorieartikel.kArtikel = tartikel.kArtikel ' . $stockFilter . '
                LEFT JOIN  tartikelsichtbarkeit
                    ON tkategorieartikel.kArtikel = tartikelsichtbarkeit.kArtikel
                        AND tartikelsichtbarkeit.kKundengruppe = :cgID
                WHERE tartikelsichtbarkeit.kArtikel IS NULL
                GROUP BY tkategorieartikel.kKategorie) AS s1 ON s1.kKategorie = node.kKategorie';
        } elseif ($filterEmpty === true) {
            $countSelect    = ', COALESCE(s1.cnt, 0) cnt';
            $visibilityJoin = ' LEFT JOIN (
                SELECT tkategorieartikel.kKategorie, COUNT(tkategorieartikel.kArtikel) AS cnt
                FROM tkategorieartikel
                LEFT JOIN  tartikelsichtbarkeit
                    ON tkategorieartikel.kArtikel = tartikelsichtbarkeit.kArtikel
                        AND tartikelsichtbarkeit.kKundengruppe = :cgID
                WHERE tartikelsichtbarkeit.kArtikel IS NULL
                GROUP BY tkategorieartikel.kKategorie) AS s1 ON s1.kKategorie = node.kKategorie';
        } else {
            // if we want to display all categories without filtering out empty ones, we don't have to check the
            // product count. this saves a very expensive join - cnt will be always -1
            $countSelect    = ', -1 AS cnt';
            $visibilityJoin = '';
        }

        return \array_map(static function (stdClass $item) {
            $item->bUnterKategorien = false;
            $item->Unterkategorien  = [];

            return new MenuItem($item);
        }, self::$db->getObjects(
            'SELECT node.kKategorie, node.lft, node.rght, node.nLevel, node.kOberKategorie, tseo.cSeo'
            . $nameSelect . $descriptionSelect . $imageSelect . $countSelect . '
                FROM (SELECT node.kKategorie, node.nLevel, node.kOberKategorie, node.cName, node.cBeschreibung,
                    node.lft, node.rght
                    FROM tkategorie AS node
                    INNER JOIN tkategorie AS parent ON node.lft BETWEEN parent.lft AND parent.rght
                    WHERE parent.kOberKategorie = :startCat
                        AND node.nLevel > :startLvl
                        AND parent.nLevel > :startLvl ' . $depthWhere .
            ') AS node ' . $langJoin . $seoJoin . $imageJoin . '
                LEFT JOIN tkategoriesichtbarkeit
                    ON node.kKategorie = tkategoriesichtbarkeit.kKategorie
                    AND tkategoriesichtbarkeit.kKundengruppe = :cgID'
            . $visibilityJoin . '
                WHERE tkategoriesichtbarkeit.kKategorie IS NULL
                ORDER BY node.lft',
            $queryParams
        ));
    }

    /**
     * @param int|null $categoryID
     * @return array
     */
    private function getAttributes(int $categoryID = null): array
    {
        $condition = $categoryID > 0
            ? ' WHERE tkategorieattribut.kKategorie = ' . $categoryID . ' '
            : '';

        return \array_map(
            static function (stdClass $e) {
                $e->kKategorie            = (int)$e->kKategorie;
                $e->bIstFunktionsAttribut = (bool)$e->bIstFunktionsAttribut;
                $e->nSort                 = (int)$e->nSort;

                return $e;
            },
            self::$db->getObjects(
                'SELECT tkategorieattribut.kKategorie, 
                    COALESCE(tkategorieattributsprache.cName, tkategorieattribut.cName) AS cName,
                    COALESCE(tkategorieattributsprache.cWert, tkategorieattribut.cWert) AS cWert,
                    tkategorieattribut.bIstFunktionsAttribut, tkategorieattribut.nSort
                FROM tkategorieattribut 
                LEFT JOIN tkategorieattributsprache 
                    ON tkategorieattributsprache.kAttribut = tkategorieattribut.kKategorieAttribut
                    AND tkategorieattributsprache.kSprache = ' . self::$languageID . $condition . '
                ORDER BY tkategorieattribut.kKategorie, tkategorieattribut.bIstFunktionsAttribut DESC, 
                tkategorieattribut.nSort'
            )
        );
    }

    /**
     * @param MenuItem[] $elements
     * @param int        $parentID
     * @param int        $rght
     * @return MenuItem[]
     */
    private function buildTree(array &$elements, int $parentID = 0, int $rght = 0): array
    {
        $branch = [];
        foreach ($elements as $j => $element) {
            if ($element->getParentID() === $parentID) {
                unset($elements[$j]);
                $children = $this->buildTree($elements, $element->getID(), $element->getRight());
                if ($children) {
                    $element->setChildren($children);
                    $element->setHasChildren(\count($children) > 0);
                }
                $branch[$element->getID()] = $element;
            } elseif ($rght !== 0 && $element->getLeft() > $rght) {
                break;
            }
        }

        return $branch;
    }

    /**
     * this must only be used in edge cases where there are very big category trees
     * and someone is looking for a bottom-up * tree for a category that is not already contained in the full tree
     *
     * it's a lot of code duplication but the queries differ
     *
     * @param int       $categoryID
     * @param bool|null $filterEmpty
     * @return MenuItem[]
     */
    public function getFallBackFlatTree(int $categoryID, ?bool $filterEmpty = null): array
    {
        $filterEmpty         = $filterEmpty ?? (int)self::$config['global']['kategorien_anzeigefilter'] ===
            \EINSTELLUNGEN_KATEGORIEANZEIGEFILTER_NICHTLEERE;
        $showCategoryImages  = self::$config['template']['megamenu']['show_category_images'] ?? 'N';
        $stockFilter         = Shop::getProductFilter()->getFilterSQL()->getStockFilterSQL();
        $stockJoin           = '';
        $extended            = !empty($stockFilter);
        $functionAttributes  = [];
        $localizedAttributes = [];
        $descriptionSelect   = ", '' AS cBeschreibung";
        $isDefaultLang       = LanguageHelper::isDefaultLanguageActive();
        $visibilityWhere     = ' AND tartikelsichtbarkeit.kArtikel IS NULL';
        $getDescription      = (!(isset(self::$config['template']['megamenu']['show_maincategory_info'])
            && isset(self::$config['template']['megamenu']['show_categories'])
            && (self::$config['template']['megamenu']['show_categories'] === 'N'
                || self::$config['template']['megamenu']['show_maincategory_info'] === 'N')));

        if ($getDescription === true) {
            $descriptionSelect = $isDefaultLang === true
                ? ', parent.cBeschreibung' //no category description needed if we don't show category info in mega menu
                : ', parent.cBeschreibung, tkategoriesprache.cBeschreibung AS cBeschreibung_spr';
        }
        $imageSelect           = $showCategoryImages === 'N'
            ? ", '' AS cPfad" // select empty path if we don't need category images for the mega menu
            : ', tkategoriepict.cPfad';
        $imageJoin             = $showCategoryImages === 'N'
            ? '' //the join is not needed if we don't select the category image path
            : ' LEFT JOIN tkategoriepict
                    ON tkategoriepict.kKategorie = node.kKategorie';
        $nameSelect            = $isDefaultLang === true
            ? ', parent.cName'
            : ', parent.cName, tkategoriesprache.cName AS cName_spr';
        $seoSelect             = ', parent.cSeo';
        $langJoin              = $isDefaultLang === true
            ? ''
            : ' LEFT JOIN tkategoriesprache
                    ON tkategoriesprache.kKategorie = node.kKategorie
                        AND tkategoriesprache.kSprache = ' . self::$languageID . ' ';
        $seoJoin               = $isDefaultLang === true
            ? ''
            : " LEFT JOIN tseo
                    ON tseo.cKey = 'kKategorie'
                    AND tseo.kKey = node.kKategorie
                    AND tseo.kSprache = " . self::$languageID . ' ';
        $hasProductssCheckJoin = ' LEFT JOIN tkategorieartikel
                ON tkategorieartikel.kKategorie = node.kKategorie ';
        if ($extended) {
            $countSelect    = ', COUNT(tartikel.kArtikel) AS cnt';
            $stockJoin      = ' LEFT JOIN tartikel
                    ON tkategorieartikel.kArtikel = tartikel.kArtikel ' . $stockFilter;
            $visibilityJoin = ' LEFT JOIN tartikelsichtbarkeit
                ON tartikel.kArtikel = tartikelsichtbarkeit.kArtikel
                AND tartikelsichtbarkeit.kKundengruppe = ' . self::$customerGroupID;
        } elseif ($filterEmpty === true) {
            $countSelect    = ', COUNT(tkategorieartikel.kArtikel) AS cnt';
            $visibilityJoin = ' LEFT JOIN tartikelsichtbarkeit
                ON tkategorieartikel.kArtikel = tartikelsichtbarkeit.kArtikel
                AND tartikelsichtbarkeit.kKundengruppe = ' . self::$customerGroupID;
        } else {
            $countSelect           = ', -1 AS cnt';
            $hasProductssCheckJoin = '';
            $visibilityJoin        = '';
            $visibilityWhere       = '';
        }

        foreach ($this->getAttributes($categoryID) as $catAttribute) {
            $catID = $catAttribute->kKategorie;
            $idx   = \mb_convert_case($catAttribute->cName, \MB_CASE_LOWER);
            if ($catAttribute->bIstFunktionsAttribut) {
                $functionAttributes[$catID][$idx] = $catAttribute->cWert;
            } else {
                $localizedAttributes[$catID][$idx] = $catAttribute;
            }
        }
        $prefix = Shop::getURL() . '/';
        $nodes  = \array_map(
            static function ($item) use ($functionAttributes, $localizedAttributes, $prefix) {
                $item->cSeo                = URL::buildURL($item, \URLART_KATEGORIE, true, $prefix);
                $item->functionAttributes  = $functionAttributes;
                $item->localizedAttributes = $localizedAttributes;

                return new MenuItem($item);
            },
            self::$db->getObjects(
                'SELECT parent.kKategorie, parent.lft, parent.rght, parent.nLevel, parent.kOberKategorie' .
                    $nameSelect . $descriptionSelect . $imageSelect . $seoSelect . $countSelect . '
                    FROM tkategorie AS node INNER JOIN tkategorie AS parent ' . $langJoin . '                    
                    LEFT JOIN tkategoriesichtbarkeit
                        ON node.kKategorie = tkategoriesichtbarkeit.kKategorie
                        AND tkategoriesichtbarkeit.kKundengruppe = ' . self::$customerGroupID
                    . $seoJoin . $imageJoin . $hasProductssCheckJoin . $stockJoin . $visibilityJoin . '
                    WHERE node.nLevel > 0 AND parent.nLevel > 0
                        AND tkategoriesichtbarkeit.kKategorie IS NULL AND node.lft BETWEEN parent.lft AND parent.rght
                        AND node.kKategorie = ' . $categoryID . $visibilityWhere . '                    
                    GROUP BY parent.kKategorie
                    ORDER BY parent.lft'
            )
        );

        if ($filterEmpty) {
            $nodes = $this->removeRelicts($this->filterEmpty($nodes));
        }

        return $nodes;
    }

    /**
     * remove items from category list that have no products and no subcategories
     *
     * @param MenuItem[] $catList
     * @return array
     */
    private function filterEmpty(array $catList): array
    {
        foreach ($catList as $i => $cat) {
            if ($cat->hasChildren() === false && $cat->getProductCount() === 0) {
                unset($catList[$i]);
            } elseif ($cat->hasChildren()) {
                $cat->setChildren($this->filterEmpty($cat->getChildren()));
            }
        }

        return $catList;
    }

    /**
     * self::filterEmpty() may have removed all sub categories from a category that now may have
     * no products and no sub categories with products in them. in this case, bUnterKategorien
     * has a wrong value and the whole category has to be removed from the result
     *
     * @param MenuItem[]    $menuItems
     * @param MenuItem|null $parentCat
     * @return MenuItem[]
     */
    private function removeRelicts(array $menuItems, ?MenuItem $parentCat = null): array
    {
        foreach ($menuItems as $i => $menuItem) {
            if ($menuItem->hasChildren() === false) {
                continue;
            }
            $menuItem->setHasChildren(\count($menuItem->getChildren()) > 0);
            if ($menuItem->getProductCount() === 0 && $menuItem->hasChildren() === false) {
                unset($menuItems[$i]);
                if ($parentCat !== null && \count($parentCat->getChildren()) === 0) {
                    $parentCat->setHasChildren(false);
                }
            } else {
                $menuItem->setChildren($this->removeRelicts($menuItem->getChildren(), $menuItem));
                if (empty($menuItem->getChildren()) && $menuItem->getProductCount() === 0) {
                    unset($menuItems[$i]);
                    if ($parentCat !== null && empty($parentCat->getChildren())) {
                        $parentCat->setHasChildren(false);
                    }
                }
            }
        }

        return $menuItems;
    }

    /**
     * check if given category ID exists in any language at all
     *
     * @param int $id
     * @return bool
     */
    public static function categoryExists(int $id): bool
    {
        return Shop::Container()->getDB()->select('tkategorie', 'kKategorie', $id) !== null;
    }

    /**
     * @param int $categoryID
     * @return bool
     */
    public static function isLostCategory(int $categoryID): bool
    {
        if (self::$lostCategories === null) {
            $cache   = Shop::Container()->getCache();
            $cacheID = self::$cacheID . '_lostCategories';
            if ((self::$lostCategories = $cache->get($cacheID)) === false) {
                self::$lostCategories = Shop::Container()->getDB()->getCollection(
                    'SELECT child.kKategorie
                    FROM tkategorie
                    LEFT JOIN tkategorie parent ON tkategorie.kOberKategorie = parent.kKategorie
                    LEFT JOIN tkategorie child ON tkategorie.lft <= child.lft AND tkategorie.rght >= child.rght
                    WHERE tkategorie.kOberKategorie > 0
                        AND parent.kKategorie IS NULL'
                )->map(static function ($item) {
                    return (int)$item->kKategorie;
                })->toArray();

                $cache->set($cacheID, self::$lostCategories, [\CACHING_GROUP_CATEGORY, 'jtl_category_tree']);
            }
        }

        return \in_array($categoryID, self::$lostCategories, true);
    }

    /**
     * @param int $id
     * @param int $lft
     * @param int $rght
     * @return MenuItem|null
     */
    public function getCategoryById(int $id, int $lft = -1, int $rght = -1): ?MenuItem
    {
        if (self::$fullCategories === null) {
            self::$fullCategories = $this->combinedGetAll();
        }
        $current = $this->findCategoryInList($id, self::$fullCategories, $lft, $rght);
        if ($current === null && (self::$limitReached || self::isLostCategory($id))) {
            // we have an incomplete category tree (because of high category count)
            // or did not find the desired category (because it is a lost category)
            $fallback = $this->getFallBackFlatTree($id);
            if (count($fallback) === 0) {
                // this category does not exists
                return null;
            }
            $current = \array_pop($fallback);
            $parent  = \array_pop($fallback);
            if ($parent !== null) {
                // get real parent category from full categories tree for further use
                $curParent = $this->findCategoryInList(
                    $parent->getID(),
                    self::$fullCategories,
                    $parent->getLeft(),
                    $parent->getRight()
                );
                if ($curParent !== null) {
                    // and fill children for current level
                    $currentChildren = $this->combinedGetAll($curParent->getID(), $curParent->getLevel());
                    if (\count($currentChildren) > 0) {
                        $curParent->setChildren($currentChildren);
                        $curParent->setHasChildren(true);
                        $current = $this->findCategoryInList(
                            $id,
                            $curParent->getChildren(),
                            $current->getLeft(),
                            $current->getRight()
                        );
                    }
                }
            }
        }

        return $current;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getChildCategoriesById(int $id): array
    {
        $current = $this->getCategoryById($id);

        return $current !== null && isset($current->Unterkategorien)
            ? \array_values($current->Unterkategorien)
            : [];
    }

    /**
     * retrieves a list of categories from a given category ID's furthest ancestor to the category itself
     *
     * @param int  $id - the base category ID
     * @param bool $noChildren - remove child categories from array?
     * @return MenuItem[]
     */
    public function getFlatTree(int $id, bool $noChildren = true): array
    {
        if (self::$fullCategories === null) {
            self::$fullCategories = $this->combinedGetAll();
        }
        $tree = [];
        $next = $this->getCategoryById($id);
        if ($next === null) {
            return $tree;
        }
        if (isset($next->kKategorie)) {
            if ($noChildren === true) {
                $cat = clone $next;
                $cat->setChildren([]);
            } else {
                $cat = $next;
            }
            $tree[] = $cat;
            while ($next !== null && !empty($next->getParentID())) {
                $next = $this->getCategoryById($next->getParentID(), $next->getLeft(), $next->getRight());
                if ($next !== null) {
                    if ($noChildren === true) {
                        $cat = clone $next;
                        $cat->setChildren([]);
                    } else {
                        $cat = $next;
                    }
                    $tree[] = $cat;
                }
            }
        }

        return \array_reverse($tree);
    }

    /**
     * @param int                 $id
     * @param MenuItem[]|MenuItem $haystack
     * @param int                 $lft
     * @param int                 $rght
     * @return MenuItem|null
     */
    private function findCategoryInList(int $id, $haystack, int $lft = -1, int $rght = -1): ?MenuItem
    {
        if (\is_array($haystack)) {
            foreach ($haystack as $category) {
                if (($result = $this->findCategoryInList($id, $category, $lft, $rght)) !== null) {
                    return $result;
                }
            }
        }
        if ($haystack instanceof MenuItem) {
            if ($haystack->getID() === $id) {
                return $haystack;
            }
            if ($haystack->hasChildren()) {
                if ($lft > -1 && $rght > -1 && ($haystack->getLeft() > $lft || $haystack->getRight() < $rght)) {
                    return null;
                }
                return $this->findCategoryInList($id, $haystack->getChildren(), $lft, $rght);
            }
        }

        return null;
    }

    /**
     * @param string|array  $attribute
     * @param string|array  $value
     * @param callable|null $callback
     * @return mixed
     * @since 5.0.0
     */
    public static function getDataByAttribute($attribute, $value, callable $callback = null)
    {
        $res = Shop::Container()->getDB()->select('tkategorie', $attribute, $value);

        return \is_callable($callback)
            ? $callback($res)
            : $res;
    }

    /**
     * @param string|array  $attribute
     * @param string|array  $value
     * @param callable|null $callback
     * @return mixed
     * @since 5.0.0
     */
    public static function getCategoryByAttribute($attribute, $value, callable $callback = null)
    {
        $cat = ($res = self::getDataByAttribute($attribute, $value)) !== null
            ? new Kategorie($res->kKategorie)
            : null;

        return \is_callable($callback)
            ? $callback($cat)
            : $cat;
    }

    /**
     * @param Kategorie $category
     * @param bool      $asString
     * @return array|string
     * @since 5.0.0
     * @former gibKategoriepfad()
     */
    public function getPath(Kategorie $category, bool $asString = true)
    {
        if (empty($category->cKategoriePfad_arr)
            || empty($category->kSprache)
            || (int)$category->kSprache !== self::$languageID
        ) {
            if (empty($category->kKategorie)) {
                return $asString ? '' : [];
            }
            $tree  = $this->getFlatTree($category->kKategorie);
            $names = [];
            foreach ($tree as $item) {
                $names[] = $item->getName();
            }
        } else {
            $names = $category->cKategoriePfad_arr;
        }

        return $asString ? \implode(' > ', $names) : $names;
    }

    /**
     * @param int $categoryID
     * @param int $left
     * @param int $right
     * @return array
     * @since 5.0.0
     * @former baueUnterkategorieListeHTML()
     */
    public static function getSubcategoryList(int $categoryID, int $left = -1, int $right = -1): array
    {
        if ($categoryID <= 0) {
            return [];
        }
        $instance = self::getInstance();
        $category = $instance->getCategoryById($categoryID, $left, $right);

        if ($category !== null
            && ((self::$limitReached && $category->getLevel() % self::$depth < 2) || self::isLostCategory($categoryID))
        ) {
            // we have an incomplete category tree and children for next two levels are probably not filled...
            $currentChildren = $instance->combinedGetAll($category->getID(), $category->getLevel());
            if (\count($currentChildren) > 0) {
                $category->setChildren($currentChildren);
                $category->setHasChildren(true);
            }
        }

        return $category === null ? [] : $category->getChildren();
    }


    /**
     * @param MenuItem[] $nodes
     * @param  array $fullCats
     * @return array
     */
    private function setOrphanedCategories(array $nodes, array $fullCats): array
    {
        $ids = \array_map(static function ($e) {
            return $e->getID();
        }, $nodes);

        $orphanedCategories = \array_filter($nodes, static function ($e) use ($ids) {
            if ($e->getParentID() === 0) {
                return false;
            }
            return \in_array($e->getParentID(), $ids, true) === false;
        });

        foreach ($orphanedCategories as $category) {
            $children = $this->buildTree($nodes, $category->getID());
            $category->setParentID(0);
            $category->setOrphaned(true);
            $category->setChildren($children);
            $category->setHasChildren(\count($children) > 0);
            $fullCats[$category->getID()] = $category;
        }

        return $fullCats;
    }

    /**
     * @param int $categoryID
     * @return bool
     */
    public function categoryHasProducts(int $categoryID): bool
    {
        if (self::$prodCatAssociations === null) {
            self::$prodCatAssociations = [];
            $data                      = Shop::Container()->getDB()->getObjects(
                'SELECT tartikel.kArtikel, tkategorieartikel.kKategorie
                    FROM tkategorieartikel, tartikel
                    LEFT JOIN tartikelsichtbarkeit 
                        ON tartikel.kArtikel = tartikelsichtbarkeit.kArtikel
                        AND tartikelsichtbarkeit.kKundengruppe = :cgid
                    WHERE tartikelsichtbarkeit.kArtikel IS NULL
                        AND tartikel.kArtikel = tkategorieartikel.kArtikel '
                . Shop::getProductFilter()->getFilterSQL()->getStockFilterSQL()
                . ' GROUP BY tkategorieartikel.kKategorie',
                ['cgid' => self::$customerGroupID]
            );
            foreach ($data as $item) {
                self::$prodCatAssociations[(int)$item->kKategorie] = 1;
            }
        }

        return isset(self::$prodCatAssociations[$categoryID]);
    }
}
