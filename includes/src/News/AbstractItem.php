<?php declare(strict_types=1);

namespace JTL\News;

use JTL\MagicCompatibilityTrait;

/**
 * Class AbstractItem
 * @package JTL\News
 */
abstract class AbstractItem implements ItemInterface
{
    use MagicCompatibilityTrait;

    /**
     * @var array
     */
    protected static $mapping = [
        'kNews'                => 'ID',
        'kSprache'             => 'LanguageID',
        'cKundengruppe'        => 'CustomerGroupsCompat',
        'cKundengruppe_arr'    => 'CustomerGroups',
        'cBetreff'             => 'Title',
        'cText'                => 'Content',
        'cVorschauText'        => 'Preview',
        'cPreviewImage'        => 'PreviewImage',
        'cMetaTitle'           => 'MetaTitle',
        'cMetaDescription'     => 'MetaDescription',
        'cMetaKeywords'        => 'MetaKeyword',
        'cISO'                 => 'LanguageCode',
        'nAktiv'               => 'IsActive',
        'cSeo'                 => 'SEO',
        'cURL'                 => 'URL',
        'cURLFull'             => 'URL',
        'dErstellt'            => 'DateCreatedCompat',
        'dErstellt_de'         => 'DateCreatedLocalizedCompat',
        'Datum'                => 'DateCompat',
        'dGueltigVon'          => 'DateValidFromCompat',
        'dGueltigVon_de'       => 'DateValidFromLocalizedCompat',
        'nNewsKommentarAnzahl' => 'CommentCount',
        'oAuthor'              => 'Author'
    ];

    /**
     * @param string|array $ssk
     * @return array
     */
    protected static function parseSSKAdvanced($ssk): array
    {
        return \is_string($ssk) && \mb_convert_case($ssk, \MB_CASE_LOWER) !== 'null'
            ? \array_map('\intval', \array_map('\trim', \array_filter(\explode(';', $ssk))))
            : [];
    }

    /**
     * @return string|null
     */
    public function getCustomerGroupsCompat(): ?string
    {
        $groups = $this->getCustomerGroups();

        return \is_array($groups) && \count($groups) > 0
            ? \implode(';', $groups) . ';'
            : null;
    }

    /**
     * @param string|array $value
     */
    public function setCustomerGroupsCompat($value): void
    {
        $this->setCustomerGroups(!\is_array($value) ? self::parseSSKAdvanced($value) : $value);
    }

    /**
     * @return string
     */
    public function getDateCreatedCompat(): string
    {
        return $this->getDateCreated()->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function getDateValidFromCompat(): string
    {
        return $this->getDateValidFrom()->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function getDateValidFromLocalizedCompat(): string
    {
        return $this->getDateValidFrom()->format('Y-m-d H:i');
    }

    /**
     * @return string
     */
    public function getDateCreatedLocalizedCompat(): string
    {
        return $this->getDateCreated()->format('d.m.Y H:i');
    }

    /**
     * @return string
     */
    public function getDateCompat(): string
    {
        return $this->getDateCreated()->format('d.m.Y H:i');
    }
}
