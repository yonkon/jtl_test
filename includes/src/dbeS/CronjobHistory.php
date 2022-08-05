<?php declare(strict_types=1);

namespace JTL\dbeS;

/**
 * Class CronjobHistory
 * @package JTL\dbeS
 */
class CronjobHistory
{
    /**
     * @var string
     */
    public $cExportformat;

    /**
     * @var string
     */
    public $cDateiname;

    /**
     * @var int
     */
    public $nDone;

    /**
     * @var string
     */
    public $cLastStartDate;

    /**
     * @param string $name
     * @param string $fileName
     * @param int    $done
     * @param string $lastStartDate
     */
    public function __construct($name, $fileName, $done, $lastStartDate)
    {
        $this->cExportformat  = $name;
        $this->cDateiname     = $fileName;
        $this->nDone          = $done;
        $this->cLastStartDate = $lastStartDate;
    }
}
