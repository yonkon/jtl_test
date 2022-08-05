<?php declare(strict_types=1);

namespace JTL\Installation;

use Cocur\Slugify\Slugify;
use Faker\Factory as Fake;
use Faker\Generator;
use JTL\DB\DbInterface;
use JTL\Installation\Faker\de_DE\Commerce;
use JTL\Installation\Faker\ImageProvider;
use JTL\xtea\XTEA;
use OverflowException;
use stdClass;

/**
 * Class DemoDataInstaller
 * @package JTL\Installation
 */
class DemoDataInstaller
{
    /**
     * number of categories to create.
     */
    public const NUM_CATEGORIES = 10;

    /**
     * number of products to create.
     */
    public const NUM_ARTICLES = 50;

    /**
     * number of manufacturers to create.
     */
    public const NUM_MANUFACTURERS = 10;

    /**
     * number of customers to create.
     */
    public const NUM_CUSTOMERS = 100;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var Slugify
     */
    private $slugify;

    /**
     * @var DbInterface
     */
    private $db;

    /**
     * @var array
     */
    private static $defaultConfig = [
        'manufacturers' => self::NUM_MANUFACTURERS,
        'categories'    => self::NUM_CATEGORIES,
        'articles'      => self::NUM_ARTICLES,
        'customers'     => self::NUM_CUSTOMERS,
    ];

    /**
     * DemoDataInstaller constructor.
     * @param DbInterface $db
     * @param array       $config
     */
    public function __construct(DbInterface $db, array $config = [])
    {
        $this->db     = $db;
        $this->config = \array_merge(static::$defaultConfig, $config);
        $this->faker  = Fake::create('de_DE');
        $this->faker->addProvider(new Commerce($this->faker));
        $this->faker->addProvider(new ImageProvider($this->faker));

        $this->slugify = new Slugify([
            'lowercase' => false,
            'rulesets'  => ['default', 'german'],
        ]);
    }

    /**
     * @param null $callback
     * @return $this
     */
    public function run($callback = null): self
    {
        $this->cleanup()
            ->addCompanyData()
            ->createManufacturers($callback)
            ->createCategories($callback)
            ->createProducts($callback)
            ->updateRatingsAvg()
            ->setConfig()
            ->updateGlobals();

        return $this;
    }

    /**
     * @return $this
     */
    public function setConfig(): self
    {
        $this->db->query(
            "UPDATE `teinstellungen`
                SET `cWert`='Y'
                WHERE `kEinstellungenSektion`='107'
                AND cName = 'bewertung_anzeigen';"
        );
        $this->db->query(
            "UPDATE `teinstellungen`
                SET `cWert`='10'
                WHERE `kEinstellungenSektion`='2'
                AND cName = 'startseite_bestseller_anzahl';"
        );
        $this->db->query(
            "UPDATE `teinstellungen`
                SET `cWert`='10'
                WHERE `kEinstellungenSektion`='2'
                AND cName = 'startseite_neuimsortiment_anzahl';"
        );
        $this->db->query(
            "UPDATE `teinstellungen`
                SET `cWert`='10'
                WHERE `kEinstellungenSektion`='2'
                AND cName = 'startseite_sonderangebote_anzahl';"
        );
        $this->db->query(
            "UPDATE `teinstellungen`
                SET `cWert`='10'
                WHERE `kEinstellungenSektion`='2'
                AND cName = 'startseite_topangebote_anzahl';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='Y'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='megamenu'
                AND `cName`='show_pages';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='Y'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='megamenu'
                AND `cName`='show_manufacturers';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='Y'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='footer'
                AND `cName`='newsletter_footer';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='Y'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='footer'
                AND `cName`='socialmedia_footer';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='https://www.facebook.com/JTLSoftware/'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='footer'
                AND `cName`='facebook';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='https://twitter.com/JTLSoftware'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='footer'
                AND `cName`='twitter';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='https://www.youtube.com/user/JTLSoftwareGmbH'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='footer'
                AND `cName`='youtube';"
        );
        $this->db->query(
            "UPDATE `ttemplateeinstellungen`
                SET `cWert`='https://www.xing.com/companies/jtl-softwaregmbh'
                WHERE `cTemplate`='NOVA'
                AND `cSektion`='footer'
                AND `cName`='xing';"
        );
        $this->db->query(
            "UPDATE `tlinksprache`
                SET `cTitle`='Startseite!', `cContent`='" . $this->faker->text(500) . "'
                WHERE `kLink`='3'
                AND `cISOSprache`='ger';"
        );
        $this->db->query(
            "UPDATE `tlinksprache`
                SET `cTitle`='Home!', `cContent`='" . $this->faker->text(500) . "'
                WHERE `kLink`=3
                AND `cISOSprache`='eng';"
        );
        $this->db->query(
            "INSERT INTO `teinheit` (`kEinheit`, `kSprache`, `cName`)
                VALUES (1,1,'kg'),(1,2,'kg'),(2,1,'ml'),(2,2,'ml'),(3,1,'Stk'),(3,2,'Piece');"
        );
        $this->db->query(
            "INSERT INTO `tlink` (`kLink`,`kVaterLink`,`kPlugin`,`cName`,`nLinkart`,`cNoFollow`,`cKundengruppen`,
            `cSichtbarNachLogin`,`cDruckButton`,`nSort`,`bSSL`,`bIsFluid`,`cIdentifier`)
                VALUES (100,0,0,'NurEndkunden',1,'N','1;','N','N',0,0,0,'');"
        );
        $this->db->query(
            "INSERT INTO `tlink` (`kLink`,`kVaterLink`,`kPlugin`,`cName`,`nLinkart`,`cNoFollow`,
          `cKundengruppen`,`cSichtbarNachLogin`,`cDruckButton`,`nSort`,`bSSL`,`bIsFluid`,`cIdentifier`)
                VALUES (101,0,0,'NurHaendler',1,'N','2;','N','N',0,0,0,'');"
        );
        $this->db->query(
            "INSERT INTO `tlink` (`kLink`,`kVaterLink`,`kPlugin`,`cName`,`nLinkart`,`cNoFollow`,
            `cKundengruppen`,`cSichtbarNachLogin`,`cDruckButton`,`nSort`,`bSSL`,`bIsFluid`,`cIdentifier`)
                VALUES (102,0,0,'Beispiel',1,'N',NULL,'N','N',0,0,0,'');"
        );
        $this->db->query(
            "INSERT INTO `tlink` (`kLink`,`kVaterLink`,`kPlugin`,`cName`,`nLinkart`,`cNoFollow`,
            `cKundengruppen`,`cSichtbarNachLogin`,`cDruckButton`,`nSort`,`bSSL`,`bIsFluid`,`cIdentifier`)
                VALUES (103,102,0,'Kindseite1',1,'N',NULL,'N','N',0,0,0,'');"
        );
        $this->db->query(
            "INSERT INTO `tlink` (`kLink`,`kVaterLink`,`kPlugin`,`cName`,`nLinkart`,`cNoFollow`,
            `cKundengruppen`,`cSichtbarNachLogin`,`cDruckButton`,`nSort`,`bSSL`,`bIsFluid`,`cIdentifier`)
                VALUES (104,102,0,'Kindseite2',1,'N',NULL,'N','N',0,0,0,'');"
        );
        $this->db->query(
            'INSERT INTO `tlinkgroupassociations` (`linkID`,`linkGroupID`)
                VALUES (100, 9), (101, 9), (102, 9), (103, 9), (104, 9);'
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
            `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (100,'customers-only','eng','Customers only','Customers only','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
            `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (100,'nur-kunden','ger','Nur Endkunden','Nur Endkunden','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
                `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (101,'retailers-only','eng','Retailers only','Retailers only','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
            `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (101,'nur-haendler','ger','Nur Haendler','Nur Haendler','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
            `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (102,'beispiel-seite','ger','Beispielseite','Beispielseite','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
            `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (103,'kindseite-eins','ger','Kindseite1','Kindseite1','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tlinksprache` (`kLink`,`cSeo`,`cISOSprache`,`cName`,`cTitle`,`cContent`,
            `cMetaTitle`,`cMetaKeywords`,`cMetaDescription`)
                VALUES (104,'kindseite-zwei','ger','Kindseite2','Kindseite2','" .
            $this->faker->text(500) . "','','','');"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('nur-endkunden', 'kLink', 100, 3);"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('customers-only', 'kLink', 100, 2);"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('nur-haendler', 'kLink', 101, 3);"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('retailers-only', 'kLink', 101, 2);"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('beispiel-seite', 'kLink', 102, 3);"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('kindseite-eins', 'kLink', 103, 3);"
        );
        $this->db->query(
            "INSERT INTO `tseo` (`cSeo`,`cKey`,`kKey`,`kSprache`) VALUES ('kindseite-zwei', 'kLink', 104, 3);"
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanup(): self
    {
        $this->db->query(
            'TRUNCATE TABLE tkategorie; TRUNCATE TABLE tartikel; TRUNCATE TABLE tartikelpict; ' .
            'TRUNCATE TABLE tkategorieartikel; TRUNCATE TABLE tbewertung; TRUNCATE TABLE tartikelext; ' .
            'TRUNCATE TABLE tkategoriepict; TRUNCATE TABLE thersteller; ' .
            'TRUNCATE TABLE tpreis; TRUNCATE TABLE tpreisdetail; TRUNCATE TABLE teinheit; TRUNCATE TABLE tkunde;'
        );
        $this->db->query('DELETE FROM tlink WHERE kLink > 99;');
        $this->db->query('DELETE FROM tlinksprache WHERE kLink > 99;');
        $this->db->query("DELETE FROM tseo WHERE cKey = 'kLink' AND kKey > 99;");
        $this->db->query(
            "DELETE FROM tseo 
                WHERE cKey = 'kArtikel' 
                    OR cKey = 'kKategorie' 
                    OR cKey = 'kHersteller'"
        );

        return $this;
    }

    /**
     * @return DemoDataInstaller
     */
    public function addCompanyData(): self
    {
        $ins                = new stdClass();
        $ins->cName         = 'Beispiel GmbH';
        $ins->cUnternehmer  = 'Max Mustermann';
        $ins->cStrasse      = 'Zufallsstraße';
        $ins->cHausnummer   = 42;
        $ins->cPLZ          = '12345';
        $ins->cOrt          = 'Beispielshausen';
        $ins->cLand         = 'Deutschland';
        $ins->cTel          = '01234 123456789';
        $ins->cFax          = '01234 123456788';
        $ins->cEMail        = 'info@example.com';
        $ins->cWWW          = 'www.example.com';
        $ins->cKontoinhaber = 'Beispiel GmbH';
        $ins->cBLZ          = '1112250000';
        $ins->cKontoNr      = '1337133713';
        $ins->cBank         = 'Sparkasse Entenhausen';
        $ins->cIBAN         = 'DE257864472';
        $ins->cBIC          = 'FOOOBAR';
        $this->db->insert('tfirma', $ins);

        return $this;
    }

    /**
     * @return int
     */
    public function updateGlobals(): int
    {
        return $this->db->getAffectedRows('UPDATE tglobals SET dLetzteAenderung = NOW()');
    }

    /**
     * @return $this
     */
    public function updateRatingsAvg(): self
    {
        $this->db->query('TRUNCATE TABLE tartikelext');
        $this->db->query(
            'INSERT INTO tartikelext(kArtikel, fDurchschnittsBewertung)
                SELECT kArtikel, AVG(nSterne) 
                FROM tbewertung GROUP BY kArtikel'
        );

        return $this;
    }

    /**
     * @param null $callback
     * @return $this
     */
    public function createManufacturers($callback = null): self
    {
        $maxPk = (int)$this->db->getSingleObject('SELECT MAX(kHersteller) AS maxPk FROM thersteller')->maxPk;
        $limit = $this->config['manufacturers'];
        $index = 0;
        for ($i = 1; $i <= $limit; ++$i) {
            try {
                $name = $this->faker->unique()->company;
                $res  = $this->db->getObjects(
                    'SELECT kHersteller 
                        FROM thersteller 
                        WHERE cName = :nm',
                    ['nm' => $name]
                );
                if (\count($res) > 0) {
                    throw new OverflowException();
                }
            } catch (OverflowException $e) {
                $name = $this->faker->unique(true)->company . '_' . ++$index;
            }

            $manufacturer              = new stdClass();
            $manufacturer->kHersteller = $maxPk + $i;
            $manufacturer->cName       = $name;
            $manufacturer->cSeo        = $this->slug($name);
            $manufacturer->cHomepage   = $this->faker->unique()->url;
            $manufacturer->nSortNr     = 0;
            $manufacturer->cBildpfad   = $this->createManufacturerImage($manufacturer->kHersteller, $name);
            $res                       = $this->db->insert('thersteller', $manufacturer);
            if ($res > 0) {
                $seoItem           = new stdClass();
                $seoItem->cKey     = 'kHersteller';
                $seoItem->cSeo     = $this->getUniqueSlug($manufacturer->cSeo);
                $seoItem->kKey     = $manufacturer->kHersteller;
                $seoItem->kSprache = 1;
                $this->db->insert('tseo', $seoItem);

                $seoItem->cSeo    .= '-en';
                $seoItem->kSprache = 2;
                $this->db->insert('tseo', $seoItem);
            }

            $this->callback($callback, $i, $limit, $res > 0, $name);
        }

        return $this;
    }

    /**
     * @param null $callback
     * @return $this
     */
    public function createCategories($callback = null): self
    {
        $maxPk   = (int)$this->db->getSingleObject('SELECT MAX(kKategorie) AS maxPk FROM tkategorie')->maxPk;
        $limit   = $this->config['categories'];
        $nameIDX = 0;
        for ($i = 1; $i <= $limit; ++$i) {
            try {
                $name = $this->faker->unique()->department;
                $res  = $this->db->getObjects(
                    'SELECT kKategorie 
                        FROM tkategorie 
                        WHERE cName = :nm',
                    ['nm' => $name]
                );
                if (\count($res) > 0) {
                    throw new OverflowException();
                }
            } catch (OverflowException $e) {
                $name = $this->faker->unique(true)->department . '_' . ++$nameIDX;
            }
            $category                        = new stdClass();
            $category->kKategorie            = $maxPk + $i;
            $category->cName                 = $name;
            $category->cSeo                  = $this->slug($name);
            $category->cBeschreibung         = $this->faker->text(200);
            $category->kOberKategorie        = \random_int(0, $category->kKategorie - 1);
            $category->nSort                 = 0;
            $category->dLetzteAktualisierung = 'now()';
            $category->lft                   = 0;
            $category->rght                  = 0;
            $res                             = $this->db->insert('tkategorie', $category);
            if ($res > 0) {
                $seo           = new stdClass();
                $seo->cKey     = 'kKategorie';
                $seo->cSeo     = $this->getUniqueSlug($category->cSeo);
                $seo->kKey     = $category->kKategorie;
                $seo->kSprache = 1;
                $this->db->insert('tseo', $seo);

                $seo->cSeo    .= '-en';
                $seo->kSprache = 2;
                $this->db->insert('tseo', $seo);

                $this->createCategoryImage($category->kKategorie, $name);
            }

            $this->callback($callback, $i, $limit, $res > 0, $name);
        }
        $this->rebuildCategoryTree(0, 1);

        return $this;
    }

    /**
     * @param null $callback
     * @return $this
     */
    public function createProducts($callback = null): self
    {
        $maxPk         = (int)$this->db->getSingleObject('SELECT MAX(kArtikel) AS cnt FROM tartikel')->cnt;
        $manufacturers = (int)$this->db->getSingleObject('SELECT COUNT(kHersteller) AS cnt FROM thersteller')->cnt;
        $categories    = (int)$this->db->getSingleObject('SELECT COUNT(kKategorie) AS cnt FROM tkategorie')->cnt;
        if ($categories === 0) {
            return $this;
        }

        $unitCount = (int)$this->db->getSingleObject(
            'SELECT MAX(groupCount) AS unitCount
                FROM (
                    SELECT COUNT(*) AS groupCount
                    FROM teinheit
                    GROUP BY kSprache
                ) x'
        )->unitCount;

        $limit   = $this->config['articles'];
        $index   = 0;
        $taxRate = 19.00;

        for ($i = 1; $i <= $limit; ++$i) {
            try {
                $name = $this->faker->unique()->productName;
                $res  = $this->db->getObjects(
                    'SELECT kArtikel 
                        FROM tartikel WHERE cName = :nm',
                    ['nm' => $name]
                );
                if (\count($res) > 0) {
                    throw new OverflowException();
                }
            } catch (OverflowException $e) {
                $name = $this->faker->unique(true)->productName . '_' . ++$index;
            }

            $price                             = \random_int(1, 2999);
            $product                           = new stdClass();
            $product->kArtikel                 = $maxPk + $i;
            $product->kHersteller              = \random_int(0, $manufacturers);
            $product->kLieferstatus            = 0;
            $product->kSteuerklasse            = 1;
            $product->kEinheit                 = (\random_int(0, 10) === 10) && $unitCount > 0
                ? \random_int(1, $unitCount)
                : 0;
            $product->kVersandklasse           = 1;
            $product->kEigenschaftKombi        = 0;
            $product->kVaterArtikel            = 0;
            $product->kStueckliste             = 0;
            $product->kWarengruppe             = 0;
            $product->kVPEEinheit              = 0;
            $product->kMassEinheit             = 0;
            $product->kGrundpreisEinheit       = 0;
            $product->cName                    = $name;
            $product->cSeo                     = $this->slug($name);
            $product->cArtNr                   = $this->faker->ean8();
            $product->cBeschreibung            = $this->faker->text(300);
            $product->cAnmerkung               = '';
            $product->fLagerbestand            = (float)\random_int(0, 1000);
            $product->fStandardpreisNetto      = $price / 19.00;
            $product->fMwSt                    = $taxRate;
            $product->fMindestbestellmenge     = (5 < \random_int(0, 10)) ? \random_int(0, 5) : 0;
            $product->fLieferantenlagerbestand = 0;
            $product->fLieferzeit              = 0;
            $product->cBarcode                 = $this->faker->ean13;
            $product->cTopArtikel              = (\random_int(0, 10) === 10) ? 'Y' : 'N';
            $product->fGewicht                 = (float)\random_int(0, 10);
            $product->fArtikelgewicht          = $product->fGewicht;
            $product->fMassMenge               = 0; //@todo?
            $product->fGrundpreisMenge         = 0;
            $product->fBreite                  = 0;
            $product->fHoehe                   = 0;
            $product->fLaenge                  = 0;
            $product->cNeu                     = (\random_int(0, 10) === 10) ? 'Y' : 'N';
            $product->cKurzBeschreibung        = $this->faker->text(50);
            $product->fUVP                     = (\random_int(0, 10) === 10) ? ($price / 2) : 0;
            $product->cLagerBeachten           = (\random_int(0, 10) === 10) ? 'Y' : 'N';
            $product->cLagerKleinerNull        = $product->cLagerBeachten;
            $product->cLagerVariation          = 'N';
            $product->cTeilbar                 = 'N';
            $product->fPackeinheit             = (\random_int(0, 10) === 10) ? \random_int(1, 12) : 1;
            $product->fAbnahmeintervall        = 0;
            $product->fZulauf                  = 0;
            $product->cVPE                     = 'N';
            $product->fVPEWert                 = 0;
            $product->nSort                    = 0;
            $product->dErscheinungsdatum       = 'now()';
            $product->dErstellt                = 'now()';
            $product->dLetzteAktualisierung    = 'now()';
            $productID                         = $this->db->insert('tartikel', $product);
            if ($productID > 0) {
                $maxImages = $this->faker->numberBetween(1, 3);
                for ($k = 0; $k < $maxImages; ++$k) {
                    $this->createProductImage($product->kArtikel, $name, $k + 1);
                }
                $numRatings = $this->faker->numberBetween(0, 6);
                for ($j = 0; $j < $numRatings; ++$j) {
                    $this->createRating($product->kArtikel);
                }
                $maxCategoryProduct = (int)$this->db->getSingleObject(
                    'SELECT MAX(kKategorieArtikel) AS cnt 
                        FROM tkategorieartikel'
                )->cnt;

                $productCategory                    = new stdClass();
                $productCategory->kKategorieArtikel = $maxCategoryProduct + 1;
                $productCategory->kArtikel          = $product->kArtikel;
                $productCategory->kKategorie        = \random_int(1, $categories);
                $this->db->insert('tkategorieartikel', $productCategory);

                $seoItem           = new stdClass();
                $seoItem->cKey     = 'kArtikel';
                $seoItem->cSeo     = $this->getUniqueSlug($product->cSeo);
                $seoItem->kKey     = $product->kArtikel;
                $seoItem->kSprache = 1;
                $this->db->insert('tseo', $seoItem);

                $seoItem->cSeo    .= '-en';
                $seoItem->kSprache = 2;
                $this->db->insert('tseo', $seoItem);

                $price2                = new stdClass();
                $price2->kArtikel      = $product->kArtikel;
                $price2->kKundengruppe = 1;
                $idxKg1                = $this->db->insert('tpreis', $price2);
                if ($idxKg1 > 0) {
                    $price3            = new stdClass();
                    $price3->kPreis    = $idxKg1;
                    $price3->nAnzahlAb = 0;
                    $price3->fVKNetto  = $price / 19.00;
                    $this->db->insert('tpreisdetail', $price3);
                }

                $price2->kKundengruppe = 2;
                $idxKg2                = $this->db->insert('tpreis', $price2);
                if ($idxKg2 > 0) {
                    $price3            = new stdClass();
                    $price3->kPreis    = $idxKg2;
                    $price3->nAnzahlAb = 0;
                    $price3->fVKNetto  = $price / 19.00;
                    $this->db->insert('tpreisdetail', $price3);
                }
            }

            $this->callback($callback, $i, $limit, $productID > 0, $name);
        }

        return $this;
    }

    /**
     * @param null $callback
     * @return $this
     */
    public function createCustomers($callback = null): self
    {
        $limit = $this->config['customers'];
        $fake  = $this->faker;
        $pdo   = $this->db;
        $xtea  = new XTEA(\BLOWFISH_KEY);
        for ($i = 1; $i <= $limit; ++$i) {
            if (\random_int(0, 1) === 0) {
                $firstName = $fake->firstNameMale;
                $gender    = 'm';
            } else {
                $firstName = $fake->firstNameFemale;
                $gender    = 'w';
            }
            $lastName      = $fake->lastName;
            $streetName    = $fake->streetName;
            $houseNr       = \random_int(1, 200);
            $cityName      = $fake->city;
            $postcode      = $fake->postcode;
            $email         = $fake->email;
            $dateofbirth   = $fake->date('Y-m-d', '1998-12-31');
            $password      = \password_hash('pass', \PASSWORD_DEFAULT);
            $streetNameEnc = $xtea->encrypt($streetName);
            $lastNameEnc   = $xtea->encrypt($lastName);
            $lastName      = $fake->lastName;

            $customer = (object)[
                'kKundengruppe'  => 1,
                'kSprache'       => 1,
                'cKundenNr'      => '',
                'cPasswort'      => $password,
                'cAnrede'        => $gender,
                'cTitel'         => '',
                'cVorname'       => $firstName,
                'cNachname'      => $lastNameEnc,
                'cFirma'         => '',
                'cZusatz'        => '',
                'cStrasse'       => $streetNameEnc,
                'cHausnummer'    => $houseNr,
                'cAdressZusatz'  => '',
                'cPLZ'           => $postcode,
                'cOrt'           => $cityName,
                'cBundesland'    => '',
                'cLand'          => 'DE',
                'cTel'           => '',
                'cMobil'         => '',
                'cFax'           => '',
                'cMail'          => $email,
                'cUSTID'         => '',
                'cWWW'           => '',
                'cSperre'        => 'N',
                'fGuthaben'      => 0.0,
                'cNewsletter'    => '',
                'dGeburtstag'    => $dateofbirth,
                'fRabatt'        => 0.0,
                'dErstellt'      => 'now()',
                'dVeraendert'    => 'now()',
                'cAktiv'         => 'Y',
                'cAbgeholt'      => 'N',
                'nRegistriert'   => 1,
                'nLoginversuche' => 0,
            ];

            $res = $pdo->insert('tkunde', $customer);
            $this->callback($callback, $i, $limit, $res > 0, $firstName . ' ' . $lastName);
        }

        return $this;
    }

    /**
     * @param string      $path
     * @param null|string $text
     * @param int         $width
     * @param int         $height
     * @return bool
     */
    private function createImage(string $path, string $text = null, int $width = 500, int $height = 500): bool
    {
        $file = $this->faker->imageFile(null, $width, $height, 'jpg', true, $text, null, null, $this->getFontFile());

        return $file !== null && \rename($file, $path);
    }

    /**
     * @param int    $manufacturerID
     * @param string $text
     * @return string
     */
    private function createManufacturerImage(int $manufacturerID, string $text): string
    {
        if ($manufacturerID <= 0) {
            return '';
        }
        $file = $this->slug($text) . '.jpg';

        return $this->createImage(\PFAD_ROOT . \STORAGE_MANUFACTURERS . $file, $text, 800, 800) === true ? $file : '';
    }

    /**
     * @param int    $productID
     * @param string $text
     * @param int    $imageNumber
     */
    private function createProductImage(int $productID, string $text, int $imageNumber): void
    {
        $maxPk = (int)$this->db->getSingleObject('SELECT MAX(kArtikelPict) AS maxPk FROM tartikelpict')->maxPk;
        if ($productID <= 0) {
            return;
        }
        $file = '1024_1024_' . \md5($text . $productID . $imageNumber) . '.jpg';
        if ($this->createImage(\PFAD_ROOT . \PFAD_MEDIA_IMAGE_STORAGE . $file, $text, 1024, 1024) === true) {
            $image                   = new stdClass();
            $image->cPfad            = $file;
            $image->kBild            = $this->db->insert('tbild', $image);
            $image->kArtikelPict     = $maxPk + 1;
            $image->kMainArtikelBild = 0;
            $image->kArtikel         = $productID;
            $image->nNr              = $imageNumber;
            $this->db->insert('tartikelpict', $image);
        }
    }

    /**
     * @param int    $categoryID
     * @param string $text
     */
    private function createCategoryImage(int $categoryID, string $text): void
    {
        if ($categoryID <= 0) {
            return;
        }
        $file = $this->slug($text) . '.jpg';
        if ($this->createImage(\PFAD_ROOT . \STORAGE_CATEGORIES . $file, $text, 200, 200) === true) {
            $this->db->insert('tkategoriepict', (object)['kKategorie' => $categoryID, 'cPfad' => $file]);
        }
    }

    /**
     * @param int $productID
     * @return bool
     */
    private function createRating(int $productID): bool
    {
        if ($productID <= 0) {
            return false;
        }
        $rating                  = new stdClass();
        $rating->kArtikel        = $productID;
        $rating->kKunde          = 0;
        $rating->kSprache        = 1;
        $rating->cName           = $this->faker->name;
        $rating->cTitel          = \addcslashes($this->faker->realText(75), '\'"');
        $rating->cText           = $this->faker->text(100);
        $rating->nHilfreich      = \random_int(0, 10);
        $rating->nNichtHilfreich = \random_int(0, 10);
        $rating->nSterne         = \random_int(1, 5);
        $rating->nAktiv          = 1;
        $rating->dDatum          = 'now()';

        return $this->db->insert('tbewertung', $rating) > 0;
    }

    /**
     * update lft/rght values for categories in the nested set model.
     *
     * @param int $parentID
     * @param int $left
     * @param int $level
     * @return int
     */
    private function rebuildCategoryTree(int $parentID, int $left, int $level = 0): int
    {
        // the right value of this node is the left value + 1
        $right = $left + 1;
        // get all children of this node
        $result = $this->db->getObjects(
            'SELECT kKategorie 
                FROM tkategorie 
                WHERE kOberKategorie = :pid
                ORDER BY nSort, cName',
            ['pid' => $parentID]
        );
        foreach ($result as $item) {
            $right = $this->rebuildCategoryTree((int)$item->kKategorie, $right, $level + 1);
        }
        // we've got the left value, and now that we've processed the children of this node we also know the right value
        $this->db->queryPrepared(
            'UPDATE tkategorie SET lft = :lft, rght = :rght, nLevel = :lvl
                WHERE kKategorie = :pid',
            ['lft' => $left, 'rght' => $right, 'lvl' => $level, 'pid' => $parentID]
        );

        // return the right value of this node + 1
        return $right + 1;
    }

    /**
     * @param string $seo
     * @return string
     */
    private function getUniqueSlug(string $seo): string
    {
        $seoIndex = 0;
        $original = $seo;
        while ($this->db->getSingleObject('SELECT cSeo FROM tseo WHERE cSeo = :seo', ['seo' => $seo]) !== null) {
            $seo = $original . '_' . ++$seoIndex;
        }

        return $seo;
    }

    /**
     * @param string $text
     * @return string
     */
    private function slug(string $text): string
    {
        return $this->slugify->slugify($text);
    }

    /**
     *
     */
    private function callback(): void
    {
        $arguments = \func_get_args();
        $cb        = \array_shift($arguments);

        if ($cb !== null && \is_callable($cb)) {
            \call_user_func_array($cb, $arguments);
        }
    }

    /**
     * @return string
     */
    private function getFontFile(): string
    {
        return \PFAD_ROOT . 'install/OpenSans-Regular.ttf';
    }
}
