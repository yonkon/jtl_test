<?php declare(strict_types=1);

namespace JTL\Country;

use JTL\MagicCompatibilityTrait;

/**
 * Class State
 * @package JTL\Country
 */
class State
{
    use MagicCompatibilityTrait;

    /**
     * @var array
     */
    protected static $mapping = [
        'kStaat'   => 'ID',
        'cLandIso' => 'CountryISO',
        'cName'    => 'Name',
        'cCode'    => 'ISO'
    ];

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $countryISO;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $iso;

    /**
     * State constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getID(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return State
     */
    public function setID(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountryISO(): string
    {
        return $this->countryISO;
    }

    /**
     * @param string $countryISO
     * @return State
     */
    public function setCountryISO(string $countryISO): self
    {
        $this->countryISO = $countryISO;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return State
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getISO(): string
    {
        return $this->iso;
    }

    /**
     * @param string $iso
     * @return State
     */
    public function setISO(string $iso): self
    {
        $this->iso = $iso;

        return $this;
    }
}
