<?php declare(strict_types=1);

namespace JTL\OPC\Portlets\Countdown;

use JTL\OPC\InputType;
use JTL\OPC\Portlet;

/**
 * Class Countdown
 * @package JTL\OPC\Portlets
 */
class Countdown extends Portlet
{
    /**
     * @return string
     */
    public function getButtonHtml(): string
    {
        return $this->getFontAwesomeButtonHtml('far fa-calendar-alt');
    }

    /**
     * @return array
     */
    public function getPropertyDesc(): array
    {
        return [
            'until'        => [
                'label'    => \__('countdownDateTime'),
                'type'     => InputType::DATETIME,
                'required' => true,
            ],
            'expired-text' => [
                'label' => \__('textAfterCountdownFinished'),
                'type'  => InputType::RICHTEXT,
            ]
        ];
    }

    /**
     * @return array
     */
    public function getPropertyTabs(): array
    {
        return [
            \__('Styles')    => 'styles',
            \__('Animation') => 'animations',
        ];
    }
}
