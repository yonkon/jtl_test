<?php

use JTL\Alert\Alert;
use JTL\Backend\Settings\Manager;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Jtllog;
use JTL\Pagination\Filter;
use JTL\Pagination\Operation;
use JTL\Pagination\Pagination;
use JTL\Shop;
use Monolog\Logger;

/**
 * @global \JTL\Smarty\JTLSmarty     $smarty
 * @global \JTL\Backend\AdminAccount $oAccount
 */
require_once __DIR__ . '/includes/admininclude.php';
$oAccount->permission('SYSTEMLOG_VIEW', true, true);

$alertHelper    = Shop::Container()->getAlertService();
$db             = Shop::Container()->getDB();
$getText        = Shop::Container()->getGetText();
$adminAccount   = Shop::Container()->getAdminAccount();
$minLogLevel    = Shop::getConfigValue(CONF_GLOBAL, 'systemlog_flag');
$settingManager = new Manager($db, $smarty, $adminAccount, $getText, $alertHelper);

if (Form::validateToken()) {
    if (Request::verifyGPDataString('action') === 'clearsyslog') {
        Jtllog::deleteAll();
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successSystemLogReset'), 'successSystemLogReset');
    } elseif (Request::verifyGPDataString('action') === 'save') {
        $minLogLevel = (int)($_POST['minLogLevel'] ?? 0);
        $db->update(
            'teinstellungen',
            'cName',
            'systemlog_flag',
            (object)['cWert' => $minLogLevel]
        );
        Shop::Container()->getCache()->flushTags([CACHING_GROUP_OPTION]);
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successConfigSave'), 'successConfigSave');
        $smarty->assign('cTab', 'config');
    } elseif (Request::verifyGPDataString('action') === 'delselected') {
        if (isset($_POST['selected'])) {
            $alertHelper->addAlert(
                Alert::TYPE_SUCCESS,
                Jtllog::deleteIDs($_POST['selected']) . __('successEntriesDelete'),
                'successEntriesDelete'
            );
        }
    }
}

$filter      = new Filter('syslog');
$levelSelect = $filter->addSelectfield(__('systemlogLevel'), 'nLevel');
$levelSelect->addSelectOption(__('all'), Operation::CUSTOM);
$levelSelect->addSelectOption(__('systemlogDebug'), Logger::DEBUG, Operation::EQUALS);
$levelSelect->addSelectOption(__('systemlogNotice'), Logger::INFO, Operation::EQUALS);
$levelSelect->addSelectOption(__('systemlogError'), Logger::ERROR, Operation::GREATER_THAN_EQUAL);
$filter->addDaterangefield(__('Zeitraum'), 'dErstellt');
$searchfield = $filter->addTextfield(__('systemlogSearch'), 'cLog', Operation::CONTAINS);
$filter->assemble();

$searchString     = $searchfield->getValue();
$selectedLevel    = $levelSelect->getSelectedOption()->getValue();
$totalLogCount    = Jtllog::getLogCount();
$filteredLogCount = Jtllog::getLogCount($searchString, (int)$selectedLevel);
$pagination       = (new Pagination('syslog'))
    ->setItemsPerPageOptions([10, 20, 50, 100, -1])
    ->setItemCount($filteredLogCount)
    ->assemble();

$logData       = Jtllog::getLogWhere($filter->getWhereSQL(), $pagination->getLimitSQL());
$systemlogFlag = Jtllog::getSytemlogFlag(false);
foreach ($logData as $log) {
    $log->kLog   = (int)$log->kLog;
    $log->nLevel = (int)$log->nLevel;
    $log->cLog   = preg_replace(
        '/\[(.*)\] => (.*)/',
        '<span class="text-primary">$1</span>: <span class="text-success">$2</span>',
        $log->cLog
    );

    if ($searchfield->getValue()) {
        $log->cLog = preg_replace(
            '/(' . preg_quote($searchfield->getValue(), '/') . ')/i',
            '<mark>$1</mark>',
            $log->cLog
        );
    }
}
$settingLogsFilter = new Filter('settingsLog');
$settingLogsFilter->addDaterangefield(
    __('Zeitraum'),
    'dDatum',
    date_create()->modify('-1 month')->format('d.m.Y') . ' - ' . date('d.m.Y')
);
$settingLogsFilter->assemble();

$settingLogsPagination = (new Pagination('settingsLog'))
    ->setItemCount($settingManager->getAllSettingLogsCount($settingLogsFilter->getWhereSQL()))
    ->assemble();

$smarty->assign('oFilter', $filter)
    ->assign('pagination', $pagination)
    ->assign('oLog_arr', $logData)
    ->assign('minLogLevel', $minLogLevel)
    ->assign('nTotalLogCount', $totalLogCount)
    ->assign('JTLLOG_LEVEL_ERROR', JTLLOG_LEVEL_ERROR)
    ->assign('JTLLOG_LEVEL_NOTICE', JTLLOG_LEVEL_NOTICE)
    ->assign('JTLLOG_LEVEL_DEBUG', JTLLOG_LEVEL_DEBUG)
    ->assign('settingLogs', $settingManager->getAllSettingLogs(
        $settingLogsFilter->getWhereSQL(),
        $settingLogsPagination->getLimitSQL()
    ))
    ->assign('settingLogsPagination', $settingLogsPagination)
    ->assign('settingLogsFilter', $settingLogsFilter)
    ->display('systemlog.tpl');
