<?php

declare(strict_types=1);

namespace Plugin\landswitcher;

use JTL\DB\DbInterface;
use JTL\Exceptions\InvalidInputException;
use JTL\IO\IOResponse;
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
            $isoCodes = $this->db->selectAll('tland', [], []);
            $this->allowedIsoCodes = array_map(function ($item) {
                return $item['cISO'];
            }, $isoCodes);
        }
        return $this->allowedIsoCodes;
    }

    public function hasIsoCode($code)
    {
        return in_array($code, $this->getIsoCodes());
    }


    public function onCreate()
    {
        $code = empty($_POST['code']) ? null : $_POST['code'];
        $url = empty($_POST['url']) ? null : $_POST['url'];

        if (!$this->validateUrl($url)) {
            throw new InvalidInputException('Invalid URL');
        }
        if (!$this->hasIsoCode($code)) {
            throw new InvalidInputException('Unknown country');
        }

        $result = new IOResponse();
        $response = new \stdClass();
        $existing = $this->db->selectSingleRow(self::TABLE, 'country_iso', $code);

        if ($existing) {
            $response->status = 'FAILED';
            $response->message = 'Redirect already exists for selected country';
        } else {
            $rowObject = new LandswitcherRedirectUrlModel($code, $url);

            $this->db->insert(self::TABLE, $rowObject);

            $response->status = 'OK';
            $response->message = 'Redirect saved successfully';
        }
        $result->assignVar('response', $response);
        return $result;
    }

    public function validateUrl($url): bool
    {
        return !!preg_match('@(https?://)|(/?/)[a-zA-Z0-9_\-]+\.[a-zA-Z0-9_\-.]+[a-zA-Z0-9]@', $url);
    }

    public function onUpdate()
    {
        $code = empty($_POST['code']) ? null : $_POST['code'];
        $url = empty($_POST['url']) ? null : $_POST['url'];

        if (!$this->validateUrl($url)) {
            throw new InvalidInputException('Invalid URL');
        }
        if (!$this->hasIsoCode($code)) {
            throw new InvalidInputException('Unknown country');
        }

        $result = new IOResponse();
        $response = new \stdClass();

        $rowObject = new LandswitcherRedirectUrlModel($code, $url);

        if ($this->db->update(self::TABLE, 'code', $code, $rowObject)) {
            $response->status = 'OK';
            $response->message = 'Redirect saved successfully';
        } else {
            $response->status = 'FAILED';
            $response->message = 'Redirect doesnt exist for selected country';
        }
        $result->assignVar('response', $response);
        return $result;
    }

    public function onDelete()
    {
        $code = empty($_POST['code']) ? null : $_POST['code'];

        if (!$this->hasIsoCode($code)) {
            throw new InvalidInputException('Unknown country');
        }

        $result = new IOResponse();
        $response = new \stdClass();


        if ($this->db->delete(self::TABLE, 'code', $code)) {
            $response->status = 'OK';
            $response->message = 'Redirect saved successfully';
        } else {
            $response->status = 'FAILED';
            $response->message = 'Redirect doesnt exist for selected country';
        }
        $result->assignVar('response', $response);
        return $result;
    }

    public function onGetOne()
    {
        $code = empty($_POST['code']) ? null : $_POST['code'];

        $result = new IOResponse();
        $response = new \stdClass();

        $redirectUrlRow = $this->db->select(self::TABLE, 'country_iso', $code);

        if (!$redirectUrlRow) {
            $response->status = 'FAILED';
            $response->message = 'Redirect doesnt exist for selected country';
            $response->item = null;
        } else {
            $response->status = 'OK';
            $response->message = 'Redirect found';
            $response->item = new LandswitcherRedirectUrlModel($redirectUrlRow['country_iso'], $redirectUrlRow['url']);
        }


        $result->assignVar('response', $response);
        return $result;
    }

    public function onGetList()
    {
        $result = new IOResponse();
        $response = new \stdClass();

        $redirectUrlRows = $this->db->selectAll(self::TABLE, [], []);

        $response->status = 'OK';
        $response->items = [];
        foreach ($redirectUrlRows as $redirectUrlRow) {
            $response->items[] = new LandswitcherRedirectUrlModel(
                $redirectUrlRow['country_iso'], $redirectUrlRow['url']
            );
        }

        $result->assignVar('response', $response);
        return $result;
    }


}
