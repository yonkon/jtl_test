<?php declare(strict_types=1);

use JTL\Shop;
use JTL\Template\Config;
use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210505144200
 */
class Migration_20210505144200 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add filter search lang and setting';

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->setLocalization('ger', 'productOverview', 'filterSearchPlaceholder', 'Suchen in %s');
        $this->setLocalization('eng', 'productOverview', 'filterSearchPlaceholder', 'Search in %s');

        $template = Shop::Container()->getTemplateService()->getActiveTemplate(false);
        $config   = new Config($template->getDir(), $this->getDB());
        $settings = Shop::getSettings([\CONF_TEMPLATE])['template'];
        if (!isset($settings['productlist']['filter_search_count'])
            && ($template->getName() === 'NOVA' || $template->getParent() === 'NOVA')
        ) {
            $config->updateConfigInDB('productlist', 'filter_search_count', '20');
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        $this->removeLocalization('filterSearchPlaceholder', 'productOverview');

        $this->execute(
            "DELETE FROM ttemplateeinstellungen
                WHERE cTemplate = 'NOVA'
                  AND cName='filter_search_count'
                  AND cSektion = 'productlist'"
        );
    }
}
