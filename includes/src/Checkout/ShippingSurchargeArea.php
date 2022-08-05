<?php declare(strict_types=1);

namespace JTL\Checkout;

/**
 * Class SurchargeArea
 * @package JTL\Checkout
 */
class ShippingSurchargeArea
{
    /**
     * @var string
     */
    public $ZIPFrom;

    /**
     * @var string
     */
    public $ZIPTo;

    /**
     * SurchargeArea constructor.
     * @param string $ZIPFrom
     * @param string $ZIPTo
     */
    public function __construct(string $ZIPFrom, string $ZIPTo)
    {
        if ($this->getNumber($ZIPFrom) < $this->getNumber($ZIPTo)) {
            $this->setZIPFrom($ZIPFrom)
                ->setZIPTo($ZIPTo);
        } else {
            $this->setZIPFrom($ZIPTo)
                ->setZIPTo($ZIPFrom);
        }
    }

    /**
     * @return string
     */
    public function getZIPFrom(): string
    {
        return $this->ZIPFrom;
    }

    /**
     * @param string $ZIPFrom
     * @return ShippingSurchargeArea
     */
    public function setZIPFrom(string $ZIPFrom): self
    {
        $this->ZIPFrom = \str_replace(' ', '', $ZIPFrom);

        return $this;
    }

    /**
     * @return string
     */
    public function getZIPTo(): string
    {
        return $this->ZIPTo;
    }

    /**
     * @param string $ZIPTo
     * @return ShippingSurchargeArea
     */
    public function setZIPTo(string $ZIPTo): self
    {
        $this->ZIPTo = \str_replace(' ', '', $ZIPTo);

        return $this;
    }

    /**
     * @param string $zip
     * @return bool
     */
    public function isInArea(string $zip): bool
    {
        $zipNumber = $this->getNumber($zip);

        return $this->getLetters($zip) === $this->getLetters($this->ZIPFrom)
            && $this->getNumber($this->ZIPFrom) <= $zipNumber
            && $this->getNumber($this->ZIPTo) >= $zipNumber;
    }

    /**
     * @return string
     */
    public function getArea(): string
    {
        return $this->ZIPFrom . ' - ' . $this->ZIPTo;
    }

    /**
     * @param $zip
     * @return int
     */
    private function getNumber($zip): int
    {
        \preg_match('/[\d]+/', $zip, $number);

        return (int)($number[0] ?? 0);
    }

    /**
     * @param $zip
     * @return string
     */
    private function getLetters($zip): string
    {
        \preg_match('/[A-Za-z]+/', $zip, $letters);

        return $letters[0] ?? '';
    }

    /**
     * @return bool
     */
    public function lettersMatch(): bool
    {
        return $this->getLetters($this->ZIPFrom) === $this->getLetters($this->ZIPTo);
    }
}
