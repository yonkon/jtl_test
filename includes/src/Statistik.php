<?php

namespace JTL;

use JTL\Helpers\Date;
use stdClass;

/**
 * Class Statistik
 * @package JTL
 */
class Statistik
{
    /**
     * @var int
     */
    private $nAnzeigeIntervall;

    /**
     * @var int
     */
    private $nTage;

    /**
     * @var int
     */
    private $nStampVon;

    /**
     * @var int
     */
    private $nStampBis;

    /**
     * @var array
     */
    private $cDatumVon_arr;

    /**
     * @var array
     */
    private $cDatumBis_arr;

    /**
     * @param int    $stampFrom
     * @param int    $stampUntil
     * @param string $dateFrom
     * @param string $dateUntil
     */
    public function __construct($stampFrom = 0, $stampUntil = 0, $dateFrom = '', $dateUntil = '')
    {
        $this->nAnzeigeIntervall = 0;
        $this->nTage             = 0;
        $this->cDatumVon_arr     = [];
        $this->cDatumBis_arr     = [];
        $this->nStampVon         = 0;
        $this->nStampBis         = 0;

        if (\mb_strlen($dateFrom) > 0 && \mb_strlen($dateUntil) > 0) {
            $this->cDatumVon_arr = Date::getDateParts($dateFrom);
            $this->cDatumBis_arr = Date::getDateParts($dateUntil);
        } elseif ((int)$stampFrom > 0 && (int)$stampUntil > 0) {
            $this->nStampVon = (int)$stampFrom;
            $this->nStampBis = (int)$stampUntil;
        }
    }

    /**
     * @param int $interval - (1) = Stunden, (2) = Tage, (3) = Monate, (4) = Jahre
     * @return array
     */
    public function holeBesucherStats(int $interval = 0): array
    {
        if (($this->nStampVon > 0 && $this->nStampBis > 0)
            || (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0)
        ) {
            $this->gibDifferenz();
            $this->gibAnzeigeIntervall();
            if ($interval > 0) {
                $this->nAnzeigeIntervall = $interval;
            }
            $dateSQL = $this->baueDatumSQL('dZeit');
            $stats   = Shop::Container()->getDB()->getObjects(
                "SELECT * , COUNT(t.dZeit) AS nCount
                    FROM (
                        SELECT dZeit, DATE_FORMAT(dZeit, '%d.%m.%Y') AS dTime,
                            DATE_FORMAT(dZeit, '%m') AS nMonth,
                            DATE_FORMAT(dZeit, '%H') AS nHour,
                            DATE_FORMAT(dZeit, '%d') AS nDay,
                            DATE_FORMAT(dZeit, '%Y') AS nYear
                        FROM tbesucherarchiv
                        " . $dateSQL->cWhere . "
                            AND kBesucherBot = 0
                        UNION ALL
                        SELECT dZeit, DATE_FORMAT( dZeit, '%d.%m.%Y' ) AS dTime,
                            DATE_FORMAT( dZeit, '%m' ) AS nMonth,
                            DATE_FORMAT( dZeit, '%H' ) AS nHour,
                            DATE_FORMAT( dZeit, '%d' ) AS nDay,
                            DATE_FORMAT( dZeit, '%Y' ) AS nYear
                        FROM tbesucher
                        " . $dateSQL->cWhere . '
                            AND kBesucherBot = 0
                    ) AS t
                    ' . $dateSQL->cGroupBy . '
                    ORDER BY dTime ASC'
            );

            return $this->mergeDaten($stats);
        }

        return [];
    }

    /**
     * @return array
     */
    public function holeKundenherkunftStats(): array
    {
        if (($this->nStampVon > 0 && $this->nStampBis > 0)
            || (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0)
        ) {
            $this->gibDifferenz();
            $this->gibAnzeigeIntervall();

            $dateSQL = $this->baueDatumSQL('dZeit');

            return Shop::Container()->getDB()->getObjects(
                "SELECT t.cReferer, SUM(t.nCount) AS nCount
                    FROM (
                        SELECT IF(cReferer = '', :directEntry, cReferer) AS cReferer,
                        COUNT(dZeit) AS nCount
                        FROM tbesucher
                        " . $dateSQL->cWhere . "
                        AND kBesucherBot = 0
                        GROUP BY cReferer
                        UNION ALL
                        SELECT IF(cReferer = '', :directEntry, cReferer) AS cReferer,
                        COUNT(dZeit) AS nCount
                        FROM tbesucherarchiv
                        " . $dateSQL->cWhere . '
                            AND kBesucherBot = 0
                        GROUP BY cReferer
                    ) AS t
                    GROUP BY t.cReferer
                    ORDER BY nCount DESC',
                ['directEntry' => \__('directEntry')]
            );
        }

        return [];
    }

    /**
     * @param int $limit
     *@return array
     */
    public function holeBotStats(int $limit = -1): array
    {
        if (($this->nStampVon > 0 && $this->nStampBis > 0) ||
            (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0)
        ) {
            $this->gibDifferenz();
            $this->gibAnzeigeIntervall();

            $dateSQL = $this->baueDatumSQL('dZeit');

            return Shop::Container()->getDB()->getObjects(
                'SELECT tbesucherbot.cUserAgent, COUNT(tbesucherbot.kBesucherBot) AS nCount
                    FROM
                        (
                            SELECT kBesucherBot
                            FROM tbesucherarchiv
                            ' . $dateSQL->cWhere . ' AND kBesucherBot > 0
                            UNION ALL
                            SELECT kBesucherBot
                            FROM tbesucher
                            ' . $dateSQL->cWhere . ' AND kBesucherBot > 0
                        ) AS t
                        JOIN tbesucherbot ON tbesucherbot.kBesucherBot = t.kBesucherBot
                    GROUP BY tbesucherbot.cUserAgent
                    ORDER BY nCount DESC ' . ($limit > -1 ? 'LIMIT ' . $limit : '')
            );
        }

        return [];
    }

    /**
     * @return array
     */
    public function holeUmsatzStats(): array
    {
        if (($this->nStampVon > 0 && $this->nStampBis > 0)
            || (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0)
        ) {
            $this->gibDifferenz();
            $this->gibAnzeigeIntervall();

            $dateSQL = $this->baueDatumSQL('tbestellung.dErstellt');

            return $this->mergeDaten(Shop::Container()->getDB()->getObjects(
                "SELECT tbestellung.dErstellt AS dZeit, SUM(tbestellung.fGesamtsumme) AS nCount,
                    DATE_FORMAT(tbestellung.dErstellt, '%m') AS nMonth,
                    DATE_FORMAT(tbestellung.dErstellt, '%H') AS nHour,
                    DATE_FORMAT(tbestellung.dErstellt, '%d') AS nDay,
                    DATE_FORMAT(tbestellung.dErstellt, '%Y') AS nYear
                    FROM tbestellung
                    " . $dateSQL->cWhere . "
                    AND cStatus != '-1'
                    " . $dateSQL->cGroupBy . '
                    ORDER BY tbestellung.dErstellt ASC'
            ));
        }

        return [];
    }

    /**
     * @return array
     */
    public function holeEinstiegsseiten(): array
    {
        if (($this->nStampVon > 0 && $this->nStampBis > 0)
            || (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0)
        ) {
            $this->gibDifferenz();
            $this->gibAnzeigeIntervall();

            $dateSQL = $this->baueDatumSQL('dZeit');

            return Shop::Container()->getDB()->getObjects(
                'SELECT t.cEinstiegsseite, COUNT(t.cEinstiegsseite) AS nCount
                    FROM
                    (
                        SELECT cEinstiegsseite
                        FROM tbesucher
                        ' .  $dateSQL->cWhere . '
                            AND kBesucherBot = 0
                        UNION ALL
                        SELECT cEinstiegsseite
                        FROM tbesucherarchiv
                        ' . $dateSQL->cWhere . '
                            AND kBesucherBot = 0
                    ) AS t
                    GROUP BY t.cEinstiegsseite
                    ORDER BY nCount DESC'
            );
        }

        return [];
    }

    /**
     * @return $this
     */
    private function gibDifferenz(): self
    {
        if (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0) {
            $dateDiff = Shop::Container()->getDB()->getSingleObject(
                'SELECT DATEDIFF(:to, :from) AS nTage',
                ['from' => $this->cDatumVon_arr['cDatum'], 'to' => $this->cDatumBis_arr['cDatum']]
            );
            if ($dateDiff !== null) {
                $this->nTage = (int)$dateDiff->nTage + 1;
            }
        } elseif ($this->nStampVon > 0 && $this->nStampBis > 0) {
            $this->nTage = ($this->nStampBis - $this->nStampVon) / 3600 / 24;
            if ($this->nTage <= 1) {
                $this->nTage = 1;
            } else {
                $this->nTage = (int)\floor($this->nTage);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function gibAnzeigeIntervall(): self
    {
        if ($this->nTage === 1) {
            $this->nAnzeigeIntervall = 1;
        } elseif ($this->nTage <= 31) { // Tage
            $this->nAnzeigeIntervall = 2;
        } elseif ($this->nTage <= 365) { // Monate
            $this->nAnzeigeIntervall = 3;
        } else { // Jahre
            $this->nAnzeigeIntervall = 4;
        }

        return $this;
    }

    /**
     * @param string $cDatumSpalte
     * @return stdClass
     */
    private function baueDatumSQL(string $cDatumSpalte): stdClass
    {
        $date           = new stdClass();
        $date->cWhere   = '';
        $date->cGroupBy = '';

        if (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0) {
            $cZeitVon = '00:00:00';
            if (isset($this->cDatumVon_arr['cZeit']) && \mb_strlen($this->cDatumVon_arr['cZeit']) > 0) {
                $cZeitVon = $this->cDatumVon_arr['cZeit'];
            }

            $cZeitBis = '23:59:59';
            if (isset($this->cDatumBis_arr['cZeit']) && \mb_strlen($this->cDatumBis_arr['cZeit']) > 0) {
                $cZeitBis = $this->cDatumBis_arr['cZeit'];
            }

            $date->cWhere = ' WHERE ' . $cDatumSpalte . " BETWEEN '" .
                $this->cDatumVon_arr['cDatum'] . ' ' . $cZeitVon . "' AND '" .
                $this->cDatumBis_arr['cDatum'] . ' ' . $cZeitBis . "' ";
        } elseif ($this->nStampVon > 0 && $this->nStampBis > 0) {
            $date->cWhere = ' WHERE ' . $cDatumSpalte . " BETWEEN '" .
                \date('Y-m-d H:i:s', $this->nStampVon) . "' AND '" .
                \date('Y-m-d H:i:s', $this->nStampBis) . "' ";
        }

        if ($this->nAnzeigeIntervall > 0) {
            switch ($this->nAnzeigeIntervall) {
                case 1: // Stunden
                    $date->cGroupBy = ' GROUP BY HOUR(' . $cDatumSpalte . ')';
                    break;

                case 2: // Tage
                    $date->cGroupBy = ' GROUP BY DAY(' . $cDatumSpalte . '), YEAR(' .
                        $cDatumSpalte . '), MONTH(' . $cDatumSpalte . ')';
                    break;

                case 3: // Monate
                    $date->cGroupBy = ' GROUP BY MONTH(' . $cDatumSpalte . '), YEAR(' . $cDatumSpalte . ')';
                    break;

                case 4: // Jahre
                    $date->cGroupBy = ' GROUP BY YEAR(' . $cDatumSpalte . ')';
                    break;
            }
        }

        return $date;
    }

    /**
     * @return array
     */
    private function vordefStats(): array
    {
        if (!$this->nAnzeigeIntervall) {
            return [];
        }
        $stats = [];
        $day   = (int)\date('d', $this->nStampVon);
        $month = (int)\date('m', $this->nStampVon);
        $year  = (int)\date('Y', $this->nStampVon);

        switch ($this->nAnzeigeIntervall) {
            case 1: // Stunden
                for ($i = 0; $i <= 23; $i++) {
                    $oStat         = new stdClass();
                    $oStat->dZeit  = \mktime($i, 0, 0, $month, $day, $year);
                    $oStat->nCount = 0;
                    $stats[]       = $oStat;
                }
                break;

            case 2: // Tage
                for ($i = 0; $i <= 30; $i++) {
                    $oStat         = new stdClass();
                    $oStat->dZeit  = \mktime(0, 0, 0, $month, $day + $i, $year);
                    $oStat->nCount = 0;
                    $stats[]       = $oStat;
                }
                break;

            case 3: // Monate
                for ($i = 0; $i <= 11; $i++) {
                    $oStat         = new stdClass();
                    $nextYear      = $month + $i > 12;
                    $monthTMP      = $nextYear ? $month + $i - 12 : $month + $i;
                    $yearTMP       = $nextYear ? $year + 1 : $year;
                    $oStat->dZeit  = \mktime(
                        0,
                        0,
                        0,
                        $monthTMP,
                        \min($day, \cal_days_in_month(CAL_GREGORIAN, $monthTMP, $yearTMP)),
                        $yearTMP
                    );
                    $oStat->nCount = 0;
                    $stats[]       = $oStat;
                }
                break;

            case 4:    // Jahre
                if (\count($this->cDatumVon_arr) > 0 && \count($this->cDatumBis_arr) > 0) {
                    $nYearFrom = (int)\date('Y', \strtotime($this->cDatumVon_arr['cDatum']));
                    $nYearTo   = (int)\date('Y', \strtotime($this->cDatumBis_arr['cDatum']));
                } elseif ($this->nStampVon > 0 && $this->nStampBis > 0) {
                    $nYearFrom = (int)\date('Y', $this->nStampVon);
                    $nYearTo   = (int)\date('Y', $this->nStampBis);
                } else {
                    $nYearFrom = (int)\date('Y') - 1;
                    $nYearTo   = (int)\date('Y') + 10;
                }
                for ($i = $nYearFrom; $i <= $nYearTo; $i++) {
                    $oStat         = new stdClass();
                    $oStat->dZeit  = \mktime(0, 0, 0, 1, 1, $i);
                    $oStat->nCount = 0;
                    $stats[]       = $oStat;
                }
                break;
        }

        return $stats;
    }

    /**
     * @param array $tmpData
     * @return array
     */
    private function mergeDaten($tmpData): array
    {
        $stats     = $this->vordefStats();
        $dayFrom   = (int)\date('d', $this->nStampVon);
        $monthFrom = (int)\date('m', $this->nStampVon);
        $yearFrom  = (int)\date('Y', $this->nStampVon);
        $dayTo     = (int)\date('d', $this->nStampBis);
        $monthTo   = (int)\date('m', $this->nStampBis);
        $yearTo    = (int)\date('Y', $this->nStampBis);
        if ($this->nStampVon !== null) {
            switch ($this->nAnzeigeIntervall) {
                case 1: // Stunden
                    $start = \mktime(0, 0, 0, $monthFrom, $dayFrom, $yearFrom);
                    $end   = \mktime(23, 59, 59, $monthTo, $dayTo, $yearTo);
                    break;

                case 2: // Tage
                    $start = \mktime(0, 0, 0, $monthFrom, $dayFrom, $yearFrom);
                    $end   = \mktime(23, 59, 59, $monthTo, $dayTo, $yearTo);
                    break;

                case 3: // Monate
                    $start = \mktime(0, 0, 0, $monthFrom, 1, $yearFrom);
                    $end   = \mktime(
                        23,
                        59,
                        59,
                        $monthTo,
                        \cal_days_in_month(CAL_GREGORIAN, $monthTo, $yearTo),
                        $yearTo
                    );
                    break;

                case 4:    // Jahre
                    $start = \mktime(0, 0, 0, 1, 1, $yearFrom);
                    $end   = \mktime(23, 59, 59, 12, 31, $yearTo);
                    break;

                default:
                    $start = 0;
                    $end   = 0;
                    break;
            }

            foreach ($stats as $i => $oStat) {
                $time = (int)$oStat->dZeit;
                if ($time < $start || $time > $end) {
                    unset($stats[$i]);
                }
            }
            $stats = \array_values($stats);
        }
        if (\count($stats) > 0 && \count($tmpData) > 0) {
            foreach ($stats as $i => $oStat) {
                $bFound = false;
                foreach ($tmpData as $oStatTMP) {
                    $bBreak = false;
                    switch ($this->nAnzeigeIntervall) {
                        case 1: // Stunden
                            if (\date('H', $oStat->dZeit) === $oStatTMP->nHour) {
                                $stats[$i]->nCount = $oStatTMP->nCount;
                                $stats[$i]->dZeit  = $oStatTMP->nHour;
                                $bBreak            = true;
                            }
                            break;

                        case 2: // Tage
                            if (\date('d.m.', $oStat->dZeit) === $oStatTMP->nDay . '.' . $oStatTMP->nMonth . '.') {
                                $stats[$i]->nCount = $oStatTMP->nCount;
                                $stats[$i]->dZeit  = $oStatTMP->nDay . '.' . $oStatTMP->nMonth . '.';
                                $bBreak            = true;
                            }
                            break;

                        case 3: // Monate
                            if (\date('m.Y', $oStat->dZeit) === $oStatTMP->nMonth . '.' . $oStatTMP->nYear) {
                                $stats[$i]->nCount = $oStatTMP->nCount;
                                $stats[$i]->dZeit  = $oStatTMP->nMonth . '.' . $oStatTMP->nYear;
                                $bBreak            = true;
                            }
                            break;

                        case 4: // Jahre
                            if (\date('Y', $oStat->dZeit) === $oStatTMP->nYear) {
                                $stats[$i]->nCount = $oStatTMP->nCount;
                                $stats[$i]->dZeit  = $oStatTMP->nYear;
                                $bBreak            = true;
                            }
                            break;
                    }

                    if ($bBreak) {
                        $bFound = true;
                        break;
                    }
                }

                if (!$bFound) {
                    switch ($this->nAnzeigeIntervall) {
                        case 1: // Stunden
                            $stats[$i]->dZeit = \date('H', $stats[$i]->dZeit);
                            break;
                        case 2: // Tage
                            $stats[$i]->dZeit = \date('d.m.', $stats[$i]->dZeit);
                            break;
                        case 3: // Monate
                            $stats[$i]->dZeit = \date('m.Y', $stats[$i]->dZeit);
                            break;
                        case 4: // Jahre
                            $stats[$i]->dZeit = \date('Y', $stats[$i]->dZeit);
                            break;
                    }
                }
            }

            return $stats;
        }

        return [];
    }

    /**
     * @param string $cDatumVon
     * @return $this
     */
    public function setDatumVon($cDatumVon): self
    {
        $this->cDatumVon_arr = Date::getDateParts($cDatumVon);

        return $this;
    }

    /**
     * @param string $cDatumBis
     * @return $this
     */
    public function setDatumBis($cDatumBis): self
    {
        $this->cDatumBis_arr = Date::getDateParts($cDatumBis);

        return $this;
    }

    /**
     * @param int $nDatumVon
     * @return $this
     */
    public function setDatumStampVon(int $nDatumVon): self
    {
        $this->nStampVon = $nDatumVon;

        return $this;
    }

    /**
     * @param int $nDatumBis
     * @return $this
     */
    public function setDatumStampBis(int $nDatumBis): self
    {
        $this->nStampBis = $nDatumBis;

        return $this;
    }

    /**
     * @return int
     */
    public function getAnzeigeIntervall(): int
    {
        if ($this->nAnzeigeIntervall === 0) {
            if ($this->nTage === 0) {
                $this->gibDifferenz();
            }

            $this->gibAnzeigeIntervall();
        }

        return $this->nAnzeigeIntervall;
    }

    /**
     * @return int
     */
    public function getAnzahlTage(): int
    {
        if ($this->nTage === 0) {
            $this->gibDifferenz();
        }

        return $this->nTage;
    }
}
