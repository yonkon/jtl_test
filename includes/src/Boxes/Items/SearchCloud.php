<?php declare(strict_types=1);

namespace JTL\Boxes\Items;

use JTL\Helpers\URL;
use JTL\Shop;

/**
 * Class SearchCloud
 * @package JTL\Boxes\Items
 */
final class SearchCloud extends AbstractBox
{
    /**
     * SearchCloud constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->addMapping('Suchbegriffe', 'Items');
        $this->addMapping('SuchbegriffeJSON', 'JSON');
        $this->setShow(false);
        $langID    = Shop::getLanguageID();
        $limit     = (int)$config['boxen']['boxen_livesuche_count'];
        $cacheID   = 'bx_stgs_' . $langID . '_' . $limit;
        $cacheTags = [\CACHING_GROUP_BOX, \CACHING_GROUP_ARTICLE];
        $cached    = true;
        if (($items = Shop::Container()->getCache()->get($cacheID)) === false) {
            $cached = false;
            $items  = Shop::Container()->getDB()->getObjects(
                "SELECT tsuchanfrage.kSuchanfrage, tsuchanfrage.kSprache, tsuchanfrage.cSuche, 
                    tsuchanfrage.nAktiv, tsuchanfrage.nAnzahlTreffer, tsuchanfrage.nAnzahlGesuche, 
                    tsuchanfrage.dZuletztGesucht, tseo.cSeo
                    FROM tsuchanfrage
                    LEFT JOIN tseo 
                        ON tseo.cKey = 'kSuchanfrage'
                        AND tseo.kKey = tsuchanfrage.kSuchanfrage
                        AND tseo.kSprache = :lid
                    WHERE tsuchanfrage.kSprache = :lid
                        AND tsuchanfrage.nAktiv = 1
                        AND tsuchanfrage.kSuchanfrage > 0
                    GROUP BY tsuchanfrage.kSuchanfrage
                    ORDER BY tsuchanfrage.nAnzahlGesuche DESC
                    LIMIT :lmt",
                ['lid' => $langID, 'lmt' => $limit]
            );
            Shop::Container()->getCache()->set($cacheID, $items, $cacheTags);
        }
        if (($count = \count($items)) > 0) {
            $prio_step = ($items[0]->nAnzahlGesuche - $items[$count - 1]->nAnzahlGesuche) / 9;
            foreach ($items as $cloudEntry) {
                $cloudEntry->Klasse   = ($prio_step < 1) ?
                    \rand(1, 10) :
                    (\round(($cloudEntry->nAnzahlGesuche - $items[$count - 1]->nAnzahlGesuche) / $prio_step) + 1);
                $cloudEntry->cURL     = URL::buildURL($cloudEntry, \URLART_LIVESUCHE);
                $cloudEntry->cURLFull = URL::buildURL($cloudEntry, \URLART_LIVESUCHE, true);
            }
            $this->setShow(true);
            \shuffle($items);
            $this->setItems($items);
            $this->setJSON(AbstractBox::getJSONString($items));
            \executeHook(\HOOK_BOXEN_INC_SUCHWOLKE, [
                'box'        => &$this,
                'cache_tags' => &$cacheTags,
                'cached'     => $cached
            ]);
        }
    }
}
