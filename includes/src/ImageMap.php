<?php

namespace JTL;

use JTL\Catalog\Product\Artikel;
use JTL\DB\DbInterface;
use JTL\Helpers\Text;
use JTL\Session\Frontend;
use stdClass;

/**
 * Class ImageMap
 * @package JTL
 */
class ImageMap implements IExtensionPoint
{
    /**
     * @var int
     */
    public $kSprache;

    /**
     * @var int
     */
    public $kKundengruppe;

    /**
     * @var DbInterface
     */
    private $db;

    /**
     * ImageMap constructor.
     * @param DbInterface $db
     */
    public function __construct(DbInterface $db)
    {
        $this->db            = $db;
        $this->kSprache      = Shop::getLanguageID();
        $this->kKundengruppe = isset($_SESSION['Kundengruppe']->kKundengruppe)
            ? Frontend::getCustomerGroup()->getID()
            : null;
        if (isset($_SESSION['Kunde']->kKundengruppe) && $_SESSION['Kunde']->kKundengruppe > 0) {
            $this->kKundengruppe = (int)$_SESSION['Kunde']->kKundengruppe;
        }
    }

    /**
     * @param int  $id
     * @param bool $fetchAll
     * @return $this
     */
    public function init($id, $fetchAll = false): self
    {
        $imageMap = $this->fetch($id, $fetchAll);
        if (\is_object($imageMap)) {
            Shop::Smarty()->assign('oImageMap', $imageMap);
        }

        return $this;
    }

    /**
     * @return stdClass[]
     */
    public function fetchAll(): array
    {
        return $this->db->getObjects(
            'SELECT *, IF(
                (CURDATE() >= DATE(vDatum)) AND (
                    bDatum IS NULL 
                    OR CURDATE() <= DATE(bDatum)
                    OR bDatum = 0), 1, 0) AS active 
                FROM timagemap
                ORDER BY cTitel ASC'
        );
    }

    /**
     * @param int  $id
     * @param bool $fetchAll
     * @param bool $fill
     * @return stdClass|bool
     */
    public function fetch(int $id, bool $fetchAll = false, bool $fill = true)
    {
        $sql = 'SELECT *
                    FROM timagemap
                    WHERE kImageMap = ' . $id;
        if (!$fetchAll) {
            $sql .= ' AND (CURDATE() >= DATE(vDatum)) AND (bDatum IS NULL OR CURDATE() <= DATE(bDatum) OR bDatum = 0)';
        }
        $imageMap = $this->db->getSingleObject($sql);
        if ($imageMap === null) {
            return false;
        }
        $imageMap->oArea_arr = $this->db->selectAll(
            'timagemaparea',
            'kImageMap',
            (int)$imageMap->kImageMap
        );
        $imageMap->cBildPfad = Shop::getImageBaseURL() . \PFAD_IMAGEMAP . $imageMap->cBildPfad;
        $parsed              = \parse_url($imageMap->cBildPfad);
        $imageMap->cBild     = \mb_substr($parsed['path'], \mb_strrpos($parsed['path'], '/') + 1);
        if (!\file_exists(\PFAD_ROOT . \PFAD_IMAGEMAP . $imageMap->cBild)) {
            return $imageMap;
        }
        [$imageMap->fWidth, $imageMap->fHeight] = \getimagesize(\PFAD_ROOT . \PFAD_IMAGEMAP . $imageMap->cBild);
        foreach ($imageMap->oArea_arr as $area) {
            $area->kImageMapArea = (int)$area->kImageMapArea;
            $area->kImageMap     = (int)$area->kImageMap;
            $area->kArtikel      = (int)$area->kArtikel;
            $area->oCoords       = new stdClass();
            $map                 = \explode(',', $area->cCoords);
            if (\count($map) === 4) {
                $area->oCoords->x = (int)$map[0];
                $area->oCoords->y = (int)$map[1];
                $area->oCoords->w = (int)$map[2];
                $area->oCoords->h = (int)$map[3];
            }
            $this->addProduct($area, $fill);
        }

        return $imageMap;
    }

    /**
     * @param stdClass $area
     * @param bool     $fill
     */
    private function addProduct(stdClass $area, bool $fill = true): void
    {
        $area->oArtikel = null;
        if ($area->kArtikel <= 0) {
            return;
        }
        $defaultOptions = Artikel::getDefaultOptions();
        $area->oArtikel = new Artikel();
        if ($fill === true) {
            $area->oArtikel->fuelleArtikel(
                $area->kArtikel,
                $defaultOptions,
                $this->kKundengruppe ?? 0,
                $this->kSprache
            );
        } else {
            $area->oArtikel->kArtikel = $area->kArtikel;
            $area->oArtikel->cName    = $this->db->select(
                'tartikel',
                'kArtikel',
                $area->kArtikel,
                null,
                null,
                null,
                null,
                false,
                'cName'
            )->cName;
        }
        if (\mb_strlen($area->cTitel) === 0) {
            $area->cTitel = $area->oArtikel->cName;
        }
        if (\mb_strlen($area->cUrl) === 0) {
            $area->cUrl = $area->oArtikel->cURL;
        }
        if (\mb_strlen($area->cBeschreibung) === 0) {
            $area->cBeschreibung = $area->oArtikel->cKurzBeschreibung;
        }
    }

    /**
     * @param string      $title
     * @param string      $imagePath
     * @param string|null $dateFrom
     * @param string|null $dateUntil
     * @return int
     */
    public function save(string $title, string $imagePath, ?string $dateFrom, ?string $dateUntil): int
    {
        $ins            = new stdClass();
        $ins->cTitel    = $title;
        $ins->cBildPfad = $imagePath;
        $ins->vDatum    = $dateFrom ?? 'NOW()';
        $ins->bDatum    = $dateUntil ?? '_DBNULL_';

        return $this->db->insert('timagemap', $ins);
    }

    /**
     * @param int         $id
     * @param string      $title
     * @param string      $imagePath
     * @param string|null $dateFrom
     * @param string|null $dateUntil
     * @return bool
     */
    public function update(int $id, string $title, string $imagePath, ?string $dateFrom, ?string $dateUntil): bool
    {
        if (empty($dateFrom)) {
            $dateFrom = 'NOW()';
        }
        if (empty($dateUntil)) {
            $dateUntil = '_DBNULL_';
        }
        $upd            = new stdClass();
        $upd->cTitel    = $title;
        $upd->cBildPfad = $imagePath;
        $upd->vDatum    = $dateFrom;
        $upd->bDatum    = $dateUntil;

        return $this->db->update('timagemap', 'kImageMap', $id, $upd) >= 0;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->db->delete('timagemap', 'kImageMap', $id) >= 0;
    }

    /**
     * @param stdClass $data
     */
    public function saveAreas(stdClass $data): void
    {
        $this->db->delete('timagemaparea', 'kImageMap', (int)$data->kImageMap);
        foreach ($data->oArea_arr as $area) {
            $ins                = new stdClass();
            $ins->kImageMap     = $area->kImageMap;
            $ins->kArtikel      = $area->kArtikel;
            $ins->cStyle        = Text::filterXSS($area->cStyle);
            $ins->cTitel        = Text::filterXSS($area->cTitel);
            $ins->cUrl          = Text::filterXSS($area->cUrl);
            $ins->cBeschreibung = Text::filterXSS($area->cBeschreibung);
            $ins->cCoords       = (int)$area->oCoords->x . ',' .
                (int)$area->oCoords->y . ',' .
                (int)$area->oCoords->w . ',' .
                (int)$area->oCoords->h;

            $this->db->insert('timagemaparea', $ins);
        }
    }
}
