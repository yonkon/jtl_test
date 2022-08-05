<?php declare(strict_types=1);

namespace JTL\Mail\Template;

/**
 * Class ProductTemplate
 * @package JTL\Mail\Template
 */
class ProductTemplate extends AbstractTemplate
{
    /**
     * replace product name with original name from db.
     *
     * @param object $data
     * @return object
     */
    protected function useOriginalName(object $data): object
    {
        if (\property_exists($data, 'tartikel')
            && \property_exists($data->tartikel, 'cName')
            && \property_exists($data->tartikel, 'originalName')
        ) {
            $data->tartikel->cName = $data->tartikel->originalName;
        }

        return $data;
    }
}
