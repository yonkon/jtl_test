<?php declare(strict_types=1);

namespace JTL\Filter\SortingOptions;

use JTL\Filter\ProductFilter;
use JTL\Shop;

/**
 * Class Weight
 * @package JTL\Filter\SortingOptions
 */
class Weight extends AbstractSortingOption
{
    /**
     * Weight constructor.
     * @param ProductFilter $productFilter
     */
    public function __construct(ProductFilter $productFilter)
    {
        parent::__construct($productFilter);
        $this->setOrderBy('tartikel.fGewicht, tartikel.cName');
        $this->setName(Shop::Lang()->get('sortWeight'));
        $this->setPriority($this->getConfig('artikeluebersicht')['suche_sortierprio_gewicht']);
        $this->setValue(\SEARCH_SORT_WEIGHT);
    }
}
