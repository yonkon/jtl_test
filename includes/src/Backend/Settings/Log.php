<?php declare(strict_types=1);

namespace JTL\Backend\Settings;

/**
 * Class Log
 * @package JTL\Backend\Settings
 */
class Log
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $adminId;

    /**
     * @var string
     */
    private $adminName;

    /**
     * @var string
     */
    private $changerIp;

    /**
     * @var string
     */
    private $settingName;

    /**
     * @var string
     */
    private $settingType;

    /**
     * @var string
     */
    private $valueOld;

    /**
     * @var string
     */
    private $valueNew;

    /**
     * @var string
     */
    private $date;

    /**
     * Log constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param \stdClass $data
     * @return Log
     */
    public function init(\stdClass $data): self
    {
        $this->setId((int)$data->kEinstellungenLog);
        $this->setAdminId((int)$data->kAdminlogin);
        $this->setAdminName($data->adminName ?? \__('unknown') . '(' . $data->kAdminlogin . ')');
        $this->setChangerIp($data->cIP ?? '');
        $this->setSettingType($data->settingType);
        $this->setSettingName($data->cEinstellungenName);
        $this->setValueNew($data->cEinstellungenWertNeu);
        $this->setValueOld($data->cEinstellungenWertAlt);
        $this->setDate($data->dDatum);

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getAdminId(): int
    {
        return $this->adminId;
    }

    /**
     * @param int $adminId
     */
    public function setAdminId(int $adminId): void
    {
        $this->adminId = $adminId;
    }

    /**
     * @return string
     */
    public function getSettingName(): string
    {
        return $this->settingName;
    }

    /**
     * @param string $settingName
     */
    public function setSettingName(string $settingName): void
    {
        $this->settingName = $settingName;
    }

    /**
     * @return string
     */
    public function getValueOld(): string
    {
        return $this->valueOld;
    }

    /**
     * @param string $valueOld
     */
    public function setValueOld(string $valueOld): void
    {
        $this->valueOld = $valueOld;
    }

    /**
     * @return string
     */
    public function getValueNew(): string
    {
        return $this->valueNew;
    }

    /**
     * @param string $valueNew
     */
    public function setValueNew(string $valueNew): void
    {
        $this->valueNew = $valueNew;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getAdminName(): string
    {
        return $this->adminName;
    }

    /**
     * @param string $adminName
     */
    public function setAdminName(string $adminName): void
    {
        $this->adminName = $adminName;
    }

    /**
     * @return string
     */
    public function getChangerIp(): string
    {
        return $this->changerIp;
    }

    /**
     * @param $cIp
     */
    public function setChangerIp($cIP): void
    {
        $this->changerIp = $cIP;
    }

    /**
     * @return string
     */
    public function getSettingType(): string
    {
        return $this->settingType;
    }

    /**
     * @param string $settingType
     */
    public function setSettingType(string $settingType): void
    {
        $this->settingType = $settingType;
    }
}
