<?php declare(strict_types=1);

namespace JTL\Boxes\Items;

use JTL\Link\LinkGroupInterface;
use JTL\Shop;

/**
 * Class LinkGroup
 * @package JTL\Boxes\Items
 */
final class LinkGroup extends AbstractBox
{
    /**
     * @var LinkGroupInterface|null
     */
    private $linkGroup;

    /**
     * @var string|null
     */
    public $linkGroupTemplate;

    /**
     * LinkGroup constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->addMapping('oLinkGruppe', 'LinkGroup');
        $this->addMapping('oLinkGruppeTemplate', 'LinkGroupTemplate');
    }

    /**
     * @inheritdoc
     */
    public function map(array $boxData): void
    {
        parent::map($boxData);
        $this->setShow(false);
        $this->linkGroup = Shop::Container()->getLinkService()->getLinkGroupByID($this->getCustomID());
        if ($this->linkGroup !== null) {
            $this->setShow($this->linkGroup->getLinks()->count() > 0);
            $this->setLinkGroupTemplate($this->linkGroup->getTemplate());
//        } else {
//            throw new \InvalidArgumentException('Cannot find link group id ' . $this->getCustomID());
        }
    }

    /**
     * @return LinkGroupInterface|null
     */
    public function getLinkGroup(): ?LinkGroupInterface
    {
        return $this->linkGroup;
    }

    /**
     * @param LinkGroupInterface|null $linkGroup
     */
    public function setLinkGroup(?LinkGroupInterface $linkGroup): void
    {
        $this->linkGroup = $linkGroup;
    }

    /**
     * @return string
     */
    public function getLinkGroupTemplate(): string
    {
        return $this->linkGroupTemplate;
    }

    /**
     * @param string $linkGroupTemplate
     */
    public function setLinkGroupTemplate(string $linkGroupTemplate): void
    {
        $this->linkGroupTemplate = $linkGroupTemplate;
    }
}
