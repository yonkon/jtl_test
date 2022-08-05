<?php declare(strict_types=1);

namespace JTL\Template;

use Exception;

/**
 * Interface TemplateServiceInterface
 * @package JTL\Template
 */
interface TemplateServiceInterface
{
    /**
     * save template data to object cache
     */
    public function save(): void;

    /**
     * reset currently active template
     */
    public function reset(): void;

    /**
     * @param bool $withLicense
     * @return Model
     * @throws Exception
     */
    public function getActiveTemplate(bool $withLicense = true): Model;

    /**
     * @param array $attributes
     * @param bool  $withLicense
     * @return Model
     * @throws Exception
     */
    public function loadFull(array $attributes, bool $withLicense = true): Model;

    /**
     * @param string $dir
     * @param string $type
     * @return bool
     */
    public function setActiveTemplate(string $dir, string $type = 'standard'): bool;
}
