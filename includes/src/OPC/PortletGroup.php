<?php declare(strict_types=1);

namespace JTL\OPC;

/**
 * Class PortletGroup
 * @package JTL\OPC
 */
class PortletGroup
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var Portlet[]
     */
    protected $portlets = [];

    /**
     * PortletGroup constructor.
     * @param string $name
     * @throws \Exception
     */
    public function __construct(string $name)
    {
        $this->name = $name === '' ? 'No Group' : $name;
    }

    /**
     * @return Portlet[]
     */
    public function getPortlets(): array
    {
        return $this->portlets;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param Portlet $portlet
     * @return $this
     */
    public function addPortlet(Portlet $portlet): self
    {
        $this->portlets[] = $portlet;

        return $this;
    }
}
