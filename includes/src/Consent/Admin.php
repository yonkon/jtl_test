<?php declare(strict_types=1);

namespace JTL\Consent;

use JTL\Model\GenericAdmin;
use Shop;

/**
 * Class Admin
 * @package JTL\Consent
 */
class Admin extends GenericAdmin
{
    /**
     * @inheritDoc
     */
    public function modelPRG(int $code = 303): void
    {
        Shop::Container()->getCache()->flushTags([\CACHING_GROUP_CORE]);
        parent::modelPRG($code);
    }
}
