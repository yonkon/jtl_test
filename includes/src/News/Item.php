<?php declare(strict_types=1);

namespace JTL\News;

use DateTime;
use InvalidArgumentException;
use JTL\ContentAuthor;
use JTL\DB\DbInterface;
use JTL\Language\LanguageHelper;
use JTL\Media\Image;
use JTL\Media\MultiSizeImage;
use JTL\Shop;
use stdClass;
use function Functional\map;

/**
 * Class Item
 * @package JTL\News
 */
class Item extends AbstractItem
{
    use MultiSizeImage;

    /**
     * @var int
     */
    protected $id = -1;

    /**
     * @var int[]
     */
    protected $languageIDs = [];

    /**
     * @var string[]
     */
    protected $languageCodes = [];

    /**
     * @var int[]
     */
    protected $customerGroups = [];

    /**
     * @var string[]
     */
    protected $titles = [];

    /**
     * @var string[]
     */
    protected $previews = [];

    /**
     * @var string[]
     */
    protected $previewImages = [];

    /**
     * @var string[]
     */
    protected $contents = [];

    /**
     * @var string[]
     */
    protected $metaTitles = [];

    /**
     * @var string[]
     */
    protected $metaKeywords = [];

    /**
     * @var string[]
     */
    protected $metaDescriptions = [];

    /**
     * @var string[]
     */
    protected $seo = [];

    /**
     * @var string[]
     */
    protected $urls = [];

    /**
     * @var bool
     */
    protected $isActive = true;

    /**
     * @var DateTime
     */
    protected $dateCreated;

    /**
     * @var DateTime
     */
    protected $dateValidFrom;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var bool
     */
    protected $isVisible = true;

    /**
     * @var CommentList
     */
    protected $comments;

    /**
     * @var int
     */
    protected $commentCount = 0;

    /**
     * @var stdClass|null
     */
    protected $author;

    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * Item constructor.
     * @param DbInterface $db
     */
    public function __construct(DbInterface $db)
    {
        $this->db            = $db;
        $this->date          = \date_create();
        $this->dateCreated   = $this->date;
        $this->dateValidFrom = $this->date;
        $this->comments      = new CommentList($this->db);
        $this->setImageType(Image::TYPE_NEWS);
    }

    /**
     * @param int $id
     * @return ItemInterface
     */
    public function load(int $id): ItemInterface
    {
        $this->id = $id;
        $item     = $this->db->getObjects(
            "SELECT tnewssprache.languageID,
                tnewssprache.languageCode,
                tnews.cKundengruppe, 
                tnewssprache.title AS localizedTitle, 
                tnewssprache.content, 
                tnewssprache.preview, 
                tnews.cPreviewImage AS previewImage, 
                tnewssprache.metaTitle, 
                tnewssprache.metaKeywords, 
                tnewssprache.metaDescription, 
                tnews.nAktiv AS isActive, 
                tnews.dErstellt AS dateCreated, 
                tnews.dGueltigVon AS dateValidFrom, 
                tseo.cSeo AS localizedURL
                FROM tnews
                JOIN tnewssprache
                    ON tnews.kNews = tnewssprache.kNews
                JOIN tseo 
                    ON tseo.cKey = 'kNews'
                    AND tseo.kKey = tnews.kNews
                    AND tseo.kSprache = tnewssprache.languageID
                WHERE tnews.kNews = :nid
                GROUP BY tnewssprache.languageID",
            ['nid' => $this->id]
        );
        if (\count($item) === 0) {
            throw new InvalidArgumentException('Provided news item id ' . $this->id . ' not found.');
        }

        return $this->map($item);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $res = [];
        foreach ($this->getLanguageIDs() as $languageID) {
            $languageCode          = $this->getLanguageCode($languageID);
            $data                  = new stdClass();
            $data->content         = $this->getContent($languageID);
            $data->url             = $this->getURL($languageID);
            $data->languageID      = $languageID;
            $data->languageCode    = $languageCode;
            $data->seo             = $this->getSEO($languageID);
            $data->id              = $this->getID();
            $data->title           = $this->getTitle($languageID);
            $data->metaDescription = $this->getMetaDescription($languageID);
            $data->metaTitle       = $this->getMetaTitle($languageID);
            $data->metaKeywords    = $this->getMetaKeyword($languageID);
            $res[$languageCode]    = $data;
        }

        return $res;
    }

    /**
     * @inheritdoc
     */
    public function map(array $localizedItems): ItemInterface
    {
        $baseURL = Shop::getURL(true) . '/';
        foreach ($localizedItems as $item) {
            $languageID = (int)$item->languageID;
            if ($languageID === 0) {
                $languageID = Shop::getLanguageID();
            }
            $this->setCustomerGroups(self::parseSSKAdvanced($item->cKundengruppe));
            $this->setLanguageCode($item->languageCode ?? Shop::getLanguageCode(), $languageID);
            $this->setContent($item->content ?? '', $languageID);
            $this->setMetaDescription($item->metaDescription ?? '', $languageID);
            $this->setMetaTitle($item->metaTitle ?? '', $languageID);
            $this->setMetaKeyword($item->metaKeywords ?? '', $languageID);
            $this->setTitle($item->localizedTitle ?? $item->cName, $languageID);
            $this->setLanguageID($languageID, $languageID);
            $this->setSEO($item->localizedURL ?? '', $languageID);
            $this->setURL($baseURL . $item->localizedURL, $languageID);
            $this->setPreview($item->preview, $languageID);
            $this->setPreviewImage($item->previewImage, $languageID);
            $this->setIsActive((int)$item->isActive === 1);
            $this->setDateCreated(\date_create($item->dateCreated));
            $this->setDate(\date_create($item->dateCreated));
            $this->setDateValidFrom(\date_create($item->dateValidFrom));
        }
        $this->comments->createItemsByNewsItem($this->id);
        $this->commentCount = $this->comments->getItems()->count();
        if (($preview = $this->getPreviewImage()) !== '') {
            $this->generateAllImageSizes(true, 1, \str_replace(\PFAD_NEWSBILDER, '', $preview));
        }
        $this->setContentAuthor();

        return $this;
    }

    private function setContentAuthor(): void
    {
        $author = ContentAuthor::getInstance()->getAuthor('NEWS', $this->getID(), true);

        if (!isset($author->kAdminlogin) || $author->kAdminlogin <= 0) {
            return;
        }
        if (isset($author->extAttribs['useAvatar']) && $author->extAttribs['useAvatar']->cAttribValue === 'U') {
            $author->cAvatarImgSrc     = $author->extAttribs['useAvatarUpload']->cAttribValue;
            $author->cAvatarImgSrcFull = Shop::getImageBaseURL() .
                \ltrim($author->extAttribs['useAvatarUpload']->cAttribValue, '/');
            unset($author->extAttribs['useAvatarUpload']);
        }

        $vitaLang = 'useVita_' . $_SESSION['cISOSprache'];
        if (isset($author->extAttribs[$vitaLang])) {
            $author->cVitaShort = $author->extAttribs[$vitaLang]->cAttribValue;
            $author->cVitaLong  = $author->extAttribs[$vitaLang]->cAttribText;
            foreach (LanguageHelper::getAllLanguages() as $language) {
                unset($author->extAttribs['useVita_' . $language->cISO]);
            }
        }
        $this->setAuthor($author);
    }

    /**
     * @return int[]
     */
    public function getCategoryIDs(): array
    {
        return map($this->db->getObjects(
            'SELECT DISTINCT(tnewskategorie.kNewsKategorie)
                FROM tnewskategorie 
                JOIN tnewskategorienews
                    ON tnewskategorienews.kNewsKategorie = tnewskategorie.kNewsKategorie
                WHERE tnewskategorienews.kNews = :nid',
            ['nid' => $this->id]
        ), static function ($e) {
            return (int)$e->kNewsKategorie;
        });
    }

    /**
     * @return stdClass[]
     */
    public function getCategories(): array
    {
        return $this->db->getObjects(
            'SELECT t.*
                FROM tnewskategorie 
                JOIN tnewskategorienews
                    ON tnewskategorienews.kNewsKategorie = tnewskategorie.kNewsKategorie
                JOIN tnewskategoriesprache t 
                    ON tnewskategorie.kNewsKategorie = t.kNewsKategorie
                WHERE tnewskategorienews.kNews = :nid',
            ['nid' => $this->id]
        );
    }

    /**
     * @param string $uploadDirName
     * @return array
     */
    public function getNewsImages(string $uploadDirName): array
    {
        $images = [];
        if ($this->id > 0 && \is_dir($uploadDirName . $this->id)) {
            $handle       = \opendir($uploadDirName . $this->id);
            $imageBaseURL = Shop::getImageBaseURL();
            while (($file = \readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..') {
                    $image           = new stdClass();
                    $image->cName    = \mb_substr($file, 0, \mb_strpos($file, '.'));
                    $image->cURL     = \PFAD_NEWSBILDER . $this->id . '/' . $file;
                    $image->cURLFull = $imageBaseURL . \PFAD_NEWSBILDER . $this->id . '/' . $file;
                    $image->cDatei   = $file;

                    $images[] = $image;
                }
            }

            \usort($images, static function ($a, $b) {
                return \strcmp($a->cName, $b->cName);
            });
        }

        return $images;
    }

    /**
     * @inheritdoc
     */
    public function checkVisibility(int $customerGroupID): bool
    {
        $cgVisi = \count($this->customerGroups) === 0
            || \in_array(-1, $this->customerGroups, true)
            || \in_array($customerGroupID, $this->customerGroups, true);

        $this->isVisible = $cgVisi && $this->isActive === true;

        return $this->isVisible;
    }

    /**
     * @inheritdoc
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function setID(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @inheritdoc
     */
    public function getSEOs(): array
    {
        return $this->seo;
    }

    /**
     * @inheritdoc
     */
    public function getSEO(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->seo[$idx] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function setSEOs(array $seo): void
    {
        $this->seo = $seo;
    }

    /**
     * @inheritdoc
     */
    public function setSEO(string $url, int $idx = null): void
    {
        $this->seo[$idx ?? Shop::getLanguageID()] = $url;
    }

    /**
     * @inheritdoc
     */
    public function getURL(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->urls[$idx] ?? '/?n=' . $this->getID();
    }

    /**
     * @inheritdoc
     */
    public function getURLs(): array
    {
        return $this->urls;
    }

    /**
     * @inheritdoc
     */
    public function setURL(string $url, int $idx = null): void
    {
        $this->urls[$idx ?? Shop::getLanguageID()] = $url;
    }

    /**
     * @inheritdoc
     */
    public function setURLs(array $urls): void
    {
        $this->urls = $urls;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->titles[$idx] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getTitleUppercase(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return \mb_convert_case($this->titles[$idx] ?? '', \MB_CASE_UPPER);
    }

    /**
     * @inheritdoc
     */
    public function getTitles(): array
    {
        return $this->titles;
    }

    /**
     * @inheritdoc
     */
    public function setTitle(string $title, int $idx = null): void
    {
        $this->titles[$idx ?? Shop::getLanguageID()] = $title;
    }

    /**
     * @inheritdoc
     */
    public function setTitles(array $title): void
    {
        $this->titles = $title;
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroups(): array
    {
        return $this->customerGroups;
    }

    /**
     * @inheritdoc
     */
    public function setCustomerGroups(array $customerGroups): void
    {
        $this->customerGroups = $customerGroups;
    }

    /**
     * @inheritdoc
     */
    public function getLanguageCode(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->languageCodes[$idx] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getLanguageCodes(): array
    {
        return $this->languageCodes;
    }

    /**
     * @inheritdoc
     */
    public function setLanguageCode(string $languageCode, int $idx = null): void
    {
        $this->languageCodes[$idx ?? Shop::getLanguageID()] = $languageCode;
    }

    /**
     * @inheritdoc
     */
    public function setLanguageCodes(array $languageCodes): void
    {
        $this->languageCodes = $languageCodes;
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @inheritdoc
     */
    public function getLanguageID(int $idx = null): int
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->languageIDs[$idx] ?? 0;
    }

    /**
     * @inheritdoc
     */
    public function setLanguageID(int $languageID, int $idx = null): void
    {
        $this->languageIDs[$idx ?? Shop::getLanguageID()] = $languageID;
    }

    /**
     * @inheritdoc
     */
    public function getLanguageIDs(): array
    {
        return $this->languageIDs;
    }

    /**
     * @inheritdoc
     */
    public function setLanguageIDs(array $ids): void
    {
        $this->languageIDs = \array_map('\intval', $ids);
    }

    /**
     * @inheritdoc
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * @inheritdoc
     */
    public function getContent(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->contents[$idx] ?? '';
    }

    /**
     * @inheritdoc
     */
    public function setContent(string $content, int $idx = null): void
    {
        $this->contents[$idx ?? Shop::getLanguageID()] = $content;
    }

    /**
     * @inheritdoc
     */
    public function setContents(array $contents): void
    {
        $this->contents = $contents;
    }

    /**
     * @inheritdoc
     */
    public function getMetaTitles(): array
    {
        return $this->metaTitles;
    }

    /**
     * @inheritdoc
     */
    public function getMetaTitle(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->isVisible ? ($this->metaTitles[$idx] ?? '') : '';
    }

    /**
     * @inheritdoc
     */
    public function setMetaTitle(string $metaTitle, int $idx = null): void
    {
        $this->metaTitles[$idx ?? Shop::getLanguageID()] = $metaTitle;
    }

    /**
     * @inheritdoc
     */
    public function setMetaTitles(array $metaTitles): void
    {
        $this->metaTitles = $metaTitles;
    }

    /**
     * @inheritdoc
     */
    public function getMetaKeyword(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->isVisible ? ($this->metaKeywords[$idx] ?? '') : '';
    }

    /**
     * @inheritdoc
     */
    public function getMetaKeywords(): array
    {
        return $this->metaKeywords;
    }

    /**
     * @inheritdoc
     */
    public function setMetaKeyword(string $metaKeyword, int $idx = null): void
    {
        $this->metaKeywords[$idx ?? Shop::getLanguageID()] = $metaKeyword;
    }

    /**
     * @inheritdoc
     */
    public function setMetaKeywords(array $metaKeywords): void
    {
        $this->metaKeywords = $metaKeywords;
    }

    /**
     * @inheritdoc
     */
    public function getMetaDescription(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->isVisible ? ($this->metaDescriptions[$idx] ?? '') : '';
    }

    /**
     * @inheritdoc
     */
    public function getMetaDescriptions(): array
    {
        return $this->metaDescriptions;
    }

    /**
     * @inheritdoc
     */
    public function setMetaDescription(string $metaDescription, int $idx = null): void
    {
        $this->metaDescriptions[$idx ?? Shop::getLanguageID()] = $metaDescription;
    }

    /**
     * @inheritdoc
     */
    public function setMetaDescriptions(array $metaDescriptions): void
    {
        $this->metaDescriptions = $metaDescriptions;
    }

    /**
     * @inheritdoc
     */
    public function getPreview(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->isVisible ? ($this->previews[$idx] ?? '') : '';
    }

    /**
     * @inheritdoc
     */
    public function getPreviews(): array
    {
        return $this->previews;
    }

    /**
     * @inheritdoc
     */
    public function setPreviews(array $previews): void
    {
        $this->previews = $previews;
    }

    /**
     * @inheritdoc
     */
    public function setPreview(string $preview, int $idx = null): void
    {
        $this->previews[$idx ?? Shop::getLanguageID()] = $preview;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewImage(int $idx = null): string
    {
        $idx = $idx ?? Shop::getLanguageID();

        return $this->isVisible ? ($this->previewImages[$idx] ?? '') : '';
    }

    /**
     * @inheritdoc
     */
    public function getPreviewImageBaseName(): string
    {
        \preg_match('/\/(.[^\/]*?)\./s', $this->getPreviewImage(), $matches);

        return $matches[1] ?? 'preview';
    }

    /**
     * @inheritdoc
     */
    public function getPreviewImages(): array
    {
        return $this->previewImages;
    }

    /**
     * @inheritdoc
     */
    public function setPreviewImages(array $previewImages): void
    {
        $this->previewImages = $previewImages;
    }

    /**
     * @inheritdoc
     */
    public function setPreviewImage(string $previewImage, int $idx = null): void
    {
        $this->previewImages[$idx ?? Shop::getLanguageID()] = $previewImage;
    }

    /**
     * @inheritdoc
     */
    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @inheritdoc
     */
    public function setDateCreated(DateTime $dateCreated): void
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @inheritdoc
     */
    public function getDateValidFrom(): DateTime
    {
        return $this->dateValidFrom;
    }

    /**
     * @inheritdoc
     */
    public function getDateValidFromNumeric(): int
    {
        return $this->dateValidFrom->getTimestamp();
    }

    /**
     * @inheritdoc
     */
    public function setDateValidFrom(DateTime $dateValidFrom): void
    {
        $this->dateValidFrom = $dateValidFrom;
    }

    /**
     * @inheritdoc
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @inheritdoc
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @inheritdoc
     */
    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    /**
     * @inheritdoc
     */
    public function setIsVisible(bool $isVisible): void
    {
        $this->isVisible = $isVisible;
    }

    /**
     * @inheritdoc
     */
    public function getComments(): CommentList
    {
        return $this->comments;
    }

    /**
     * @inheritdoc
     */
    public function setComments(CommentList $comments): void
    {
        $this->comments     = $comments;
        $this->commentCount = $comments->getItems()->count();
    }

    /**
     * @inheritdoc
     */
    public function getCommentCount(): int
    {
        return $this->commentCount;
    }

    /**
     * @inheritdoc
     */
    public function setCommentCount(int $commentCount): void
    {
        $this->commentCount = $commentCount;
    }

    /**
     * @return null|stdClass
     */
    public function getAuthor(): ?stdClass
    {
        return $this->author;
    }

    /**
     * @param null|stdClass $author
     */
    public function setAuthor(?stdClass $author): void
    {
        $this->author = $author;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $res       = \get_object_vars($this);
        $res['db'] = '*truncated*';

        return $res;
    }
}
