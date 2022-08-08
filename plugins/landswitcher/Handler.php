<?php

declare(strict_types=1);

namespace Plugin\landswitcher;

use JTL\DB\DbInterface;
use JTL\Exceptions\InvalidInputException;
use JTL\Plugin\PluginInterface;
use Plugin\landswitcher\Models\LandswitcherRedirectUrlModel;

class Handler
{
    /**
     * @var DbInterface
     */
    private $db;

    /**
     * @var PluginInterface
     */
    private $plugin;

    /**
     * @var array|null
     */
    private $allowedIsoCodes = null;

    const TABLE = 'landswitcher_redirect_url';

    /**
     * Handler constructor.
     * @param PluginInterface $plugin
     * @param DbInterface $db
     */
    public function __construct(PluginInterface $plugin, DbInterface $db)
    {
        $this->db = $db;
        $this->plugin = $plugin;
    }

    public function getIsoCodes()
    {
        if (is_null($this->allowedIsoCodes)) {
            $isoCodes = $this->db->selectArray('tland', [], [], 'cISO');
            $this->allowedIsoCodes = array_map(function ($item) {
                return $item->cISO;
            }, $isoCodes);
        }
        return $this->allowedIsoCodes;
    }

    public function hasIsoCode($country_iso)
    {
        return in_array($country_iso, $this->getIsoCodes());
    }

    public function validateUrl($url): bool
    {
        return !!preg_match('@((https?://)|(/?/))?[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-.]+[a-zA-Z0-9]\S*@', $url);
    }


    public function onCreate($country_iso, $url)
    {
        if (!$this->validateUrl($url)) {
            throw new InvalidInputException('Invalid URL', $url);
        }
        if (!$this->hasIsoCode($country_iso)) {
            throw new InvalidInputException('Unknown country', $country_iso);
        }

        $response = new \stdClass();
        $existing = $this->db->selectSingleRow(self::TABLE, 'country_iso', $country_iso);

        if ($existing) {
            throw new InvalidInputException('Redirect already exists for selected country', $country_iso);
        } else {
            $rowObject = new LandswitcherRedirectUrlModel($country_iso, $url);

            $this->db->insert(self::TABLE, $rowObject);

            $response->status = 'OK';
            $response->message = 'Redirect saved successfully';
            return $response;
        }
    }


    public function onUpdate($country_iso, $url)
    {
        if (!$this->validateUrl($url)) {
            throw new InvalidInputException('Invalid URL', $url);
        }
        if (!$this->hasIsoCode($country_iso)) {
            throw new InvalidInputException('Unknown country', $country_iso);
        }

        $response = new \stdClass();

        $rowObject = new LandswitcherRedirectUrlModel($country_iso, $url);

        if ($this->db->update(self::TABLE, 'country_iso', $country_iso, $rowObject)) {
            $response->status = 'OK';
            $response->message = 'Redirect saved successfully';
            return $response;
        }

        throw new InvalidInputException('Redirect doesnt exist for selected country', $country_iso);
    }

    public function onDelete($country_iso)
    {
        if (!$this->hasIsoCode($country_iso)) {
            throw new InvalidInputException('Unknown country', $country_iso);
        }

        $response = new \stdClass();

        if ($this->db->delete(self::TABLE, 'country_iso', $country_iso)) {
            $response->status = 'OK';
            $response->message = 'Redirect saved successfully';
            return $response;
        }
        throw new InvalidInputException('Redirect doesnt exist for selected country', $country_iso);
    }

    public function onGetOne($country_iso)
    {
        $response = new \stdClass();

        $redirectUrlRow = $this->db->getSingleObject(
            '
SELECT t.*, tland.cDeutsch as name 
FROM ' . self::TABLE . ' t
JOIN tland 
    ON tland.cISO = t.country_iso  
 WHERE t.country_iso = :country_iso',
            ['country_iso' => $country_iso]
        );

        if (!$redirectUrlRow) {
            throw new InvalidInputException('Redirect doesnt exist for selected country', $country_iso);
        }


        $response->status = 'OK';
        $response->message = 'Redirect found';
        $response->item = $redirectUrlRow;
        return $response;
    }

    public function onGetList()
    {
        $response = new \stdClass();

        $response->status = 'OK';
        $response->items = $this->db->getObjects(
            '
SELECT t.*, tland.cDeutsch as name 
FROM ' . self::TABLE . ' t
JOIN tland 
    ON tland.cISO = t.country_iso ',
        );


        return $response;
    }


}
