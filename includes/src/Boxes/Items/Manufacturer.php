<?php declare(strict_types=1);

namespace JTL\Boxes\Items;

use JTL\Helpers\Manufacturer as ManufacturerHelper;

/**
 * Class Manufacturer
 *
 * @package JTL\Boxes\Items
 */
final class Manufacturer extends AbstractBox
{
    /**
     * @var array
     */
    private $manufacturerList;

    /**
     * Manufacturer constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $manufacturers = ManufacturerHelper::getInstance()->getManufacturers();
        $count         = $config['boxen']['box_hersteller_anzahl_anzeige'];

        $this->addMapping('manufacturers', 'Manufacturers');
        $this->setManufacturers(
            $count > 0
                ? \array_slice($manufacturers, 0, $config['boxen']['box_hersteller_anzahl_anzeige'])
                : $manufacturers
        );
        $this->setShow(\count($this->manufacturerList) > 0);
    }

    /**
     * @return array
     */
    public function getManufacturers(): array
    {
        return $this->manufacturerList;
    }

    /**
     * @param array $manufacturers
     */
    public function setManufacturers(array $manufacturers): void
    {
        $this->manufacturerList = $manufacturers;
    }
}
