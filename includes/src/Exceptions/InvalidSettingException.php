<?php

namespace JTL\Exceptions;

use Exception;

/**
 * Class InvalidSettingException
 * @package JTL\Exceptions
 */
class InvalidSettingException extends Exception
{
    /**
     * InvalidSettingException constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct('Einstellungsfehler: ' . $message);
    }
}
