<?php declare(strict_types=1);

namespace JTL\OPC\Portlets\Accordion;

use JTL\OPC\InputType;
use JTL\OPC\Portlet;

/**
 * Class Accordion
 * @package JTL\OPC\Portlets
 */
class Accordion extends Portlet
{
    /**
     * @return array
     */
    public function getPropertyDesc(): array
    {
        return [
            'groups' => [
                'type' => InputType::TEXT_LIST,
                'label' => \__('groupName'),
                'default' => [\__('groupName')]
            ],
            'expanded' => [
                'type' => InputType::CHECKBOX,
                'label' => \__('unfoldFirstGroup')
            ]
        ];
    }

    /**
     * @return array
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Styles') => 'styles',
        ];
    }
}
