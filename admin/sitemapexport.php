<?php

use JTL\Alert\Alert;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Pagination\Pagination;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('EXPORT_SITEMAP_VIEW', true, true);
$alertHelper = Shop::Container()->getAlertService();
$db          = Shop::Container()->getDB();
if (!file_exists(PFAD_ROOT . PFAD_EXPORT . 'sitemap_index.xml') && is_writable(PFAD_ROOT . PFAD_EXPORT)) {
    @touch(PFAD_ROOT . PFAD_EXPORT . 'sitemap_index.xml');
}

if (!is_writable(PFAD_ROOT . PFAD_EXPORT . 'sitemap_index.xml')) {
    $alertHelper->addAlert(
        Alert::TYPE_ERROR,
        sprintf(__('errorSitemapCreatePermission'), '<i>' . PFAD_ROOT . PFAD_EXPORT . 'sitemap_index.xml</i>'),
        'errorSitemapCreatePermission'
    );
} elseif (isset($_REQUEST['update']) && (int)$_REQUEST['update'] === 1) {
    $alertHelper->addAlert(
        Alert::TYPE_SUCCESS,
        sprintf(__('successSave'), '<i>' . PFAD_ROOT . PFAD_EXPORT . 'sitemap_index.xml</i>'),
        'successSubjectDelete'
    );
}

if (Request::postInt('einstellungen') > 0) {
    $alertHelper->addAlert(
        Alert::TYPE_SUCCESS,
        saveAdminSectionSettings(CONF_SITEMAP, $_POST),
        'saveSettings'
    );
} elseif (Request::verifyGPCDataInt('download_edit') === 1) {
    $trackers = array_map('\intval', Request::postVar('kSitemapTracker', []));
    if (count($trackers) > 0) {
        $db->query(
            'DELETE
                FROM tsitemaptracker
                WHERE kSitemapTracker IN (' . implode(',', $trackers) . ')'
        );
    }
    $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successSitemapDLDelete'), 'successSitemapDLDelete');
} elseif (Request::verifyGPCDataInt('report_edit') === 1) {
    $reports = array_map('\intval', Request::postVar('kSitemapReport', []));
    if (count($reports) > 0) {
        $db->query(
            'DELETE
                FROM tsitemapreport
                WHERE kSitemapReport IN (' . implode(',', $reports) . ')'
        );
    }
    $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successSitemapReportDelete'), 'successSitemapReportDelete');
}

$yearDownloads = Request::verifyGPCDataInt('nYear_downloads');
$yearReports   = Request::verifyGPCDataInt('nYear_reports');

if (Request::postVar('action') === 'year_downloads_delete' && Form::validateToken()) {
    $db->queryPrepared(
        'DELETE FROM tsitemaptracker
            WHERE YEAR(tsitemaptracker.dErstellt) = :yr',
        ['yr' => $yearDownloads]
    );
    $alertHelper->addAlert(
        Alert::TYPE_SUCCESS,
        sprintf(__('successSitemapDLDeleteByYear'), $yearDownloads),
        'successSitemapDLDeleteByYear'
    );
    $yearDownloads = 0;
}

if (Request::postVar('action') === 'year_reports_delete' && Form::validateToken()) {
    $db->queryPrepared(
        'DELETE FROM tsitemapreport
            WHERE YEAR(tsitemapreport.dErstellt) = :yr',
        ['yr' => $yearReports]
    );
    $alertHelper->addAlert(
        Alert::TYPE_SUCCESS,
        sprintf(__('successSitemapReportDeleteByYear'), $yearDownloads),
        'successSitemapReportDeleteByYear'
    );
    $yearReports = 0;
}

$sitemapDownloadsPerYear = $db->getObjects(
    'SELECT YEAR(dErstellt) AS year, COUNT(*) AS count
        FROM tsitemaptracker
        GROUP BY 1
        ORDER BY 1 DESC'
);
if (count($sitemapDownloadsPerYear) === 0) {
    $sitemapDownloadsPerYear[] = (object)[
        'year'  => date('Y'),
        'count' => 0,
    ];
}
if ($yearDownloads === 0) {
    $yearDownloads = (int)$sitemapDownloadsPerYear[0]->year;
}
$downloadPagination = (new Pagination('SitemapDownload'))
    ->setItemCount(array_reduce($sitemapDownloadsPerYear, static function ($carry, $item) use ($yearDownloads) {
        return (int)$item->year === $yearDownloads ? (int)$item->count : $carry;
    }, 0))
    ->assemble();
$sitemapDownloads   = $db->getObjects(
    "SELECT tsitemaptracker.*, IF(tsitemaptracker.kBesucherBot = 0, '', 
        IF(CHAR_LENGTH(tbesucherbot.cUserAgent) = 0, tbesucherbot.cName, tbesucherbot.cUserAgent)) AS cBot, 
        DATE_FORMAT(tsitemaptracker.dErstellt, '%d.%m.%Y %H:%i') AS dErstellt_DE
        FROM tsitemaptracker
        LEFT JOIN tbesucherbot 
            ON tbesucherbot.kBesucherBot = tsitemaptracker.kBesucherBot
        WHERE YEAR(tsitemaptracker.dErstellt) = :yr
        ORDER BY tsitemaptracker.dErstellt DESC
        LIMIT " . $downloadPagination->getLimitSQL(),
    ['yr' => $yearDownloads]
);

// Sitemap Reports
$reportYears = $db->getObjects(
    'SELECT YEAR(dErstellt) AS year, COUNT(*) AS count
        FROM tsitemapreport
        GROUP BY 1
        ORDER BY 1 DESC'
);
if (count($reportYears) === 0) {
    $reportYears[] = (object)[
        'year'  => date('Y'),
        'count' => 0,
    ];
}
if ($yearReports === 0) {
    $yearReports = (int)$reportYears[0]->year;
}
$pagination     = (new Pagination('SitemapReport'))
    ->setItemCount(array_reduce($reportYears, static function ($carry, $item) use ($yearReports) {
        return (int)$item->year === $yearReports ? (int)$item->count : $carry;
    }, 0))
    ->assemble();
$sitemapReports = $db->getObjects(
    "SELECT tsitemapreport.*, DATE_FORMAT(tsitemapreport.dErstellt, '%d.%m.%Y %H:%i') AS dErstellt_DE
        FROM tsitemapreport
        WHERE YEAR(tsitemapreport.dErstellt) = :yr
        ORDER BY tsitemapreport.dErstellt DESC
        LIMIT " . $pagination->getLimitSQL(),
    ['yr' => $yearReports]
);
foreach ($sitemapReports as $report) {
    if (isset($report->kSitemapReport) && $report->kSitemapReport > 0) {
        $report->oSitemapReportFile_arr = $db->selectAll(
            'tsitemapreportfile',
            'kSitemapReport',
            (int)$report->kSitemapReport
        );
    }
}

$smarty->assign('oConfig_arr', getAdminSectionSettings(CONF_SITEMAP))
       ->assign('nSitemapDownloadYear', $yearDownloads)
       ->assign('oSitemapDownloadYears_arr', $sitemapDownloadsPerYear)
       ->assign('oSitemapDownloadPagination', $downloadPagination)
       ->assign('oSitemapDownload_arr', $sitemapDownloads)
       ->assign('nSitemapReportYear', $yearReports)
       ->assign('oSitemapReportYears_arr', $reportYears)
       ->assign('oSitemapReportPagination', $pagination)
       ->assign('oSitemapReport_arr', $sitemapReports)
       ->assign('URL', Shop::getURL() . '/' . 'sitemap_index.xml')
       ->display('sitemapexport.tpl');
