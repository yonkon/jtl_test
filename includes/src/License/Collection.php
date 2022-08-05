<?php declare(strict_types=1);

namespace JTL\License;

use JTL\License\Struct\ExsLicense;
use JTL\License\Struct\License;

/**
 * Class Collection
 * @package JTL\License
 */
class Collection extends \Illuminate\Support\Collection
{
    /**
     * @return Collection
     */
    public function getActive(): self
    {
        return $this->getBound();
    }

    /**
     * @return Collection
     */
    public function getBound(): self
    {
        return $this->filter(static function (ExsLicense $e) {
            return $e->getState() === ExsLicense::STATE_ACTIVE;
        });
    }

    /**
     * @return Collection
     */
    public function getUnbound(): self
    {
        return $this->filter(static function (ExsLicense $e) {
            return $e->getState() === ExsLicense::STATE_UNBOUND;
        });
    }

    /**
     * @param string $itemID
     * @return ExsLicense|null
     */
    public function getForItemID(string $itemID): ?ExsLicense
    {
        $matches = $this->getBound()->filter(static function (ExsLicense $e) use ($itemID) {
            return $e->getID() === $itemID;
        })->sort(static function (ExsLicense $e) {
            return $e->getLicense()->getType() === License::TYPE_PROD ? -1 : 1;
        });
        if ($matches->count() > 1) {
            foreach ($matches as $exs) {
                $license = $exs->getLicense();
                if ($license->isExpired() === false && $license->getSubscription()->isExpired() === false) {
                    return $exs;
                }
            }
        }

        return $matches->first();
    }

    /**
     * @param string $exsID
     * @return ExsLicense|null
     */
    public function getForExsID(string $exsID): ?ExsLicense
    {
        $matches = $this->getBound()->filter(static function (ExsLicense $e) use ($exsID) {
            return $e->getExsID() === $exsID;
        })->sort(static function (ExsLicense $e) {
            return $e->getLicense()->getType() === License::TYPE_PROD ? -1 : 1;
        });
        if ($matches->count() > 1) {
            // when there are multiple bound exs licenses, try to choose one that isn't expired yet
            foreach ($matches as $exs) {
                $license = $exs->getLicense();
                if ($license->isExpired() === false && $license->getSubscription()->isExpired() === false) {
                    return $exs;
                }
            }
        }

        return $matches->first();
    }

    /**
     * @param string $licenseKey
     * @return ExsLicense|null
     */
    public function getForLicenseKey(string $licenseKey): ?ExsLicense
    {
        return $this->first(static function (ExsLicense $e) use ($licenseKey) {
            return $e->getLicense()->getKey() === $licenseKey;
        });
    }

    /**
     * @return Collection
     */
    public function getActiveExpired(): self
    {
        return $this->getBoundExpired()->filter(static function (ExsLicense  $e) {
            $ref = $e->getReferencedItem();

            return $ref !== null && $ref->isActive();
        });
    }

    /**
     * @return Collection
     */
    public function getDedupedActiveExpired(): self
    {
        return $this->getActiveExpired()->filter(function (ExsLicense $e) {
            return $e === $this->getForExsID($e->getExsID());
        });
    }

    /**
     * @return Collection
     */
    public function getBoundExpired(): self
    {
        return $this->getBound()->filter(static function (ExsLicense $e) {
            $ref = $e->getReferencedItem();

            return $ref !== null
                && ($e->getLicense()->isExpired() || $e->getLicense()->getSubscription()->isExpired());
        });
    }

    /**
     * @return Collection
     */
    public function getLicenseViolations(): self
    {
        return $this->getDedupedActiveExpired()->filter(static function (ExsLicense $e) {
            return !$e->canBeUsed();
        });
    }

    /**
     * @return Collection
     */
    public function getExpiredActiveTests(): self
    {
        return $this->getExpiredBoundTests();
    }

    /**
     * @return Collection
     */
    public function getExpiredBoundTests(): self
    {
        return $this->getBoundExpired()->filter(static function (ExsLicense $e) {
            return $e->getLicense()->getType() === License::TYPE_TEST;
        });
    }

    /**
     * @return Collection
     */
    public function getDedupedExpiredBoundTests(): self
    {
        return $this->getExpiredBoundTests()->filter(function (ExsLicense $e) {
            return $e === $this->getForExsID($e->getExsID());
        });
    }

    /**
     * @return Collection
     */
    public function getPlugins(): self
    {
        return $this->filter(static function (ExsLicense $e) {
            return $e->getType() === ExsLicense::TYPE_PLUGIN || $e->getType() === ExsLicense::TYPE_PORTLET;
        });
    }

    /**
     * @return Collection
     */
    public function getTemplates(): self
    {
        return $this->filter(static function (ExsLicense $e) {
            return $e->getType() === ExsLicense::TYPE_TEMPLATE;
        });
    }

    /**
     * @return Collection
     */
    public function getPortlets(): self
    {
        return $this->filter(static function (ExsLicense $e) {
            return $e->getType() === ExsLicense::TYPE_PORTLET;
        });
    }

    /**
     * @return Collection
     */
    public function getInstalled(): self
    {
        return $this->getBound()->filter(static function (ExsLicense $e) {
            return $e->getReferencedItem() !== null;
        });
    }

    /**
     * @return Collection
     */
    public function getUpdateableItems(): self
    {
        return $this->getBound()->getInstalled()->filter(static function (ExsLicense $e) {
            return $e->getReferencedItem()->hasUpdate() === true;
        });
    }

    /**
     * @return Collection
     */
    public function getExpired(): self
    {
        return $this->getBound()->filter(static function (ExsLicense $e) {
            return $e->getLicense()->isExpired() || $e->getLicense()->getSubscription()->isExpired();
        });
    }

    /**
     * @param int $days
     * @return Collection
     */
    public function getAboutToBeExpired(int $days = 28): self
    {
        return $this->getBound()->filter(static function (ExsLicense $e) use ($days) {
            $license = $e->getLicense();

            return (!$license->isExpired()
                    && $license->getDaysRemaining() > 0
                    && $license->getDaysRemaining() < $days)
                || (!$license->getSubscription()->isExpired()
                    && $license->getSubscription()->getDaysRemaining() > 0
                    && $license->getSubscription()->getDaysRemaining() < $days
                );
        });
    }
}
