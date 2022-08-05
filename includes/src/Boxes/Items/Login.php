<?php declare(strict_types=1);

namespace JTL\Boxes\Items;

/**
 * Class Login
 *
 * @package JTL\Boxes\Items
 */
final class Login extends AbstractBox
{
    /**
     * Login constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->setShow(true);
    }
}
