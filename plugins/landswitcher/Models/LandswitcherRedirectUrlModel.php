<?php

declare(strict_types=1);

namespace Plugin\landswitcher\Models;

class LandswitcherRedirectUrlModel
{
    public $country_iso;
    public $url;

    public function __construct($country_iso, $url)
    {
        $this->country_iso = $country_iso;
        $this->url = $url;
    }

}
