<?php declare(strict_types=1);

namespace JTL\Console\Command\Cache;

use JTL\Cache\JTLCacheInterface;
use JTL\Catalog\Category\Kategorie;
use JTL\Catalog\Hersteller;
use JTL\Catalog\Product\Artikel;
use JTL\Console\Command\Command;
use JTL\Customer\CustomerGroup;
use JTL\DB\DbInterface;
use JTL\Helpers\Tax;
use JTL\Language\LanguageHelper;
use JTL\Language\LanguageModel;
use JTL\Link\LinkGroupList;
use JTL\Shop;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class WarmCacheCommand
 * @package JTL\Console\Command\Cache
 */
class WarmCacheCommand extends Command
{
    /**
     * @var bool
     */
    private $details = true;

    /**
     * @var bool
     */
    private $list = true;

    /**
     * @var bool
     */
    private $categories = false;

    /**
     * @var bool
     */
    private $links = false;

    /**
     * @var bool
     */
    private $manufacturers = false;

    /**
     * @var bool
     */
    private $preFlush = false;

    /**
     * @var bool
     */
    private $childProducts = false;

    /**
     * @var DbInterface
     */
    private $db;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('cache:warm')
            ->setDescription('Warm object cache')
            ->addOption('details', 'd', InputOption::VALUE_NONE, 'Warm product details')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Warm product lists')
            ->addOption('childproducts', 'k', InputOption::VALUE_NONE, 'Warm product lists')
            ->addOption('linkgroups', 'g', InputOption::VALUE_NONE, 'Warm link groups')
            ->addOption('categories', 'c', InputOption::VALUE_NONE, 'Warm categories')
            ->addOption('manufacturers', 'm', InputOption::VALUE_NONE, 'Warm manufacturers')
            ->addOption('preflush', 'p', InputOption::VALUE_NONE, 'Flush cache before run');
    }

    /**
     * @param string $msg
     */
    private function debug(string $msg): void
    {
        $io = $this->getIO();
        if (!$io->isVerbose()) {
            return;
        }
        $io->writeln($msg);
    }

    /**
     * @param CustomerGroup[] $customerGroups
     * @param LanguageModel[] $languages
     * @return int
     */
    private function warmProducts(array $customerGroups, array $languages): int
    {
        if ($this->details === false && $this->list === false) {
            return 0;
        }
        $generated = 0;
        $where     = $this->childProducts ? '' : ' WHERE kVaterArtikel = 0';
        $listOpt   = Artikel::getDefaultOptions();
        $detailOpt = Artikel::getDetailOptions();
        $total     = (int)$this->db->getSingleObject('SELECT COUNT(kArtikel) AS cnt FROM tartikel' . $where)->cnt;
        $bar       = new ProgressBar($this->getIO(), $total);
        $bar->setFormat('cache');
        $bar->setMessage('Warming products');
        $bar->start();
        foreach ($this->db->getObjects('SELECT kArtikel AS id FROM tartikel' . $where) as $item) {
            $pid = (int)$item->id;
            foreach ($customerGroups as $customerGroup) {
                $_SESSION['Kundengruppe'] = $customerGroup;
                Tax::setTaxRates();
                foreach ($languages as $language) {
                    $languageID              = $language->getId();
                    $_SESSION['kSprache']    = $languageID;
                    $_SESSION['cISOSprache'] = $language->getCode();
                    Shop::setLanguage($languageID, $language->getCode());
                    if ($this->details === true) {
                        $product = (new Artikel())->fuelleArtikel(
                            $pid,
                            $detailOpt,
                            $customerGroup->getID(),
                            $languageID
                        );
                        ++$generated;
                        $this->debug('Details for product ' . $pid
                            . ' in language ' . $languageID
                            . ' for customer group ' . $customerGroup->getID()
                            . ($product !== null && $product->kArtikel > 0
                                ? ' successfully loaded'
                                : ' could not be loaded'));
                    }
                    if ($this->list === true) {
                        $product = (new Artikel())->fuelleArtikel(
                            $pid,
                            $listOpt,
                            $customerGroup->getID(),
                            $languageID
                        );
                        ++$generated;
                        $this->debug('List view for product ' . $pid
                            . ' in language ' . $languageID
                            . ' for customer group ' . $customerGroup->getID()
                            . ($product !== null && $product->kArtikel > 0
                                ? ' successfully loaded'
                                : ' could not be loaded'));
                    }
                }
            }
            $bar->advance();
            $bar->setMessage('Loaded product ' . $pid);
        }
        $bar->setMessage('All products loaded');
        $bar->finish();
        $this->getIO()->newLine();
        $this->getIO()->newLine();

        return $generated;
    }

    /**
     * @param CustomerGroup[] $customerGroups
     * @param LanguageModel[] $languages
     * @return int
     */
    private function warmCategories(array $customerGroups, array $languages): int
    {
        if ($this->categories !== true) {
            return 0;
        }
        $generated = 0;
        $total     = (int)$this->db->getSingleObject('SELECT COUNT(kKategorie) AS cnt FROM tkategorie')->cnt;
        $bar       = new ProgressBar($this->getIO(), $total);
        $bar->setFormat('cache');
        $bar->setMessage('Warming categories');
        $bar->start();
        foreach ($this->db->getObjects('SELECT kKategorie FROM tkategorie') as $item) {
            $cid = (int)$item->kKategorie;
            foreach ($customerGroups as $customerGroup) {
                foreach ($languages as $language) {
                    $category = new Kategorie($cid, $language->getId(), $customerGroup->getID(), false);
                    ++$generated;
                    $this->debug('Category ' . $cid
                        . ($category->kKategorie > 0 ? ' successfully' : ' could not be')
                        . ' loaded in language ' . $language->getId()
                        . ' with customer group ' . $customerGroup->getID());
                }
            }
            $bar->advance();
            $bar->setMessage('Loaded category ' . $cid);
        }
        $bar->setMessage('All categories loaded');
        $bar->finish();
        $this->getIO()->newLine();
        $this->getIO()->newLine();

        return $generated;
    }

    /**
     * @param LanguageModel[] $languages
     * @return int
     */
    private function warmManufacturers(array $languages): int
    {
        if ($this->manufacturers !== true) {
            return 0;
        }
        $generated = 0;
        $total     = (int)$this->db->getSingleObject('SELECT COUNT(kHersteller) AS cnt FROM thersteller')->cnt;
        $bar       = new ProgressBar($this->getIO(), $total);
        $bar->setFormat('cache');
        $bar->setMessage('Warming manufacturers');
        $bar->start();
        foreach ($this->db->getObjects('SELECT kHersteller FROM thersteller') as $item) {
            $mid = (int)$item->kHersteller;
            foreach ($languages as $language) {
                $manufacturer = new Hersteller($mid, $language->getId());
                ++$generated;
                $this->debug('Manufacturer ' . $mid
                    . ($manufacturer->kHersteller > 0 ? ' successfully' : ' could not be')
                    . ' loaded in language ' . $language->getId());
            }
            $bar->advance();
            $bar->setMessage('Loaded manufacturer ' . $mid);
        }
        $bar->setMessage('All manufacturers loaded');
        $bar->finish();
        $this->getIO()->newLine();
        $this->getIO()->newLine();

        return $generated;
    }

    /**
     * @param JTLCacheInterface $cache
     * @return int
     */
    private function warmLinks(JTLCacheInterface $cache): int
    {
        if ($this->links === false) {
            return 0;
        }
        $total = (int)$this->db->getSingleObject('SELECT COUNT(*) AS cnt FROM tlinkgruppe')->cnt + 3;
        $bar   = new ProgressBar($this->getIO(), $total);
        $bar->setFormat('cache');
        $bar->setMessage('Warming link groups');
        $lgl = new LinkGroupList($this->db, $cache);
        $lgl->loadAll();
        $bar->start();
        $bar->advance($total);
        $bar->setMessage('All link groups loaded');
        $bar->finish();
        $this->getIO()->newLine();
        $this->getIO()->newLine();

        return $total;
    }

    private function warm(): void
    {
        $start    = \microtime(true);
        $io       = $this->getIO();
        $this->db = Shop::Container()->getDB();
        $cache    = Shop::Container()->getCache();
        ProgressBar::setFormatDefinition(
            'cache',
            " \033[44;37m %message:-37s% \033[0m\n %current%/%max% %bar% %percent:3s%%"
        );
        Shop::setProductFilter(Shop::buildProductFilter([]));
        if ($this->preFlush === true) {
            $cache->flushAll();
        }
        if (\strpos(\URL_SHOP, 'https://') === 0
            || Shop::getSettingValue(\CONF_GLOBAL, 'kaufabwicklung_ssl_nutzen') === 'P'
        ) {
            $_SERVER['HTTPS'] = 'on';
        }

        global $_SESSION;
        $_SESSION             = [];
        $_SESSION['Sprachen'] = LanguageHelper::getInstance($this->db, $cache)->gibInstallierteSprachen();

        $generated      = 0;
        $customerGroups = $this->db->getCollection('SELECT kKundengruppe AS id FROM tkundengruppe')
            ->map(static function ($e) {
                return new CustomerGroup((int)$e->id);
            })
            ->toArray();
        $languages      = LanguageModel::loadAll($this->db, [], [])->toArray();

        $generated += $this->warmCategories($customerGroups, $languages);
        $generated += $this->warmProducts($customerGroups, $languages);
        $generated += $this->warmManufacturers($languages);
        $generated += $this->warmLinks($cache);

        $cacheStats = $cache->getStats();
        $io->writeln('Entries in cache: ' . $cacheStats['entries'] . \PHP_EOL . 'Used Memory: ' . $cacheStats['mem']);
        $io->success('Generated ' . $generated . ' cache entries for '
            . \count($customerGroups) . ' customer group(s) and '
            . \count($languages) . ' language(s) in '
            . \number_format(\microtime(true) - $start, 4) . 's.');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->db            = Shop::Container()->getDB();
        $this->details       = $this->getOption('details');
        $this->list          = $this->getOption('list');
        $this->childProducts = $this->getOption('childproducts');
        $this->categories    = $this->getOption('categories');
        $this->links         = $this->getOption('linkgroups');
        $this->manufacturers = $this->getOption('manufacturers');
        $this->preFlush      = $this->getOption('preflush');
        $this->warm();

        return Command::SUCCESS;
    }
}
