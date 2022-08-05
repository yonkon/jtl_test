<?php

use JTL\Cron\QueueEntry;
use JTL\Export\FormatExporter;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'exportformat_inc.php';

@ini_set('max_execution_time', '0');

if (Request::getInt('e') < 1 || !Form::validateToken()) {
    die('0');
}
Shop::Container()->getGetText()->loadAdminLocale('pages/exportformate');
$db    = Shop::Container()->getDB();
$queue = $db->select('texportqueue', 'kExportqueue', Request::getInt('e'));
if (!isset($queue->kExportformat) || !$queue->kExportformat || !$queue->nLimit_m) {
    die('1');
}
$ef = new FormatExporter($db, Shop::Container()->getLogService());

$queue->jobQueueID    = (int)$queue->kExportqueue;
$queue->cronID        = 0;
$queue->foreignKeyID  = 0;
$queue->taskLimit     = (int)$queue->nLimit_m;
$queue->tasksExecuted = (int)$queue->nLimit_n;
$queue->lastProductID = (int)$queue->nLastArticleID;
$queue->jobType       = 'exportformat';
$queue->tableName     = null;
$queue->foreignKey    = 'kExportformat';
$queue->foreignKeyID  = (int)$queue->kExportformat;

try {
    $ef->startExport(
        (int)$queue->kExportformat,
        new QueueEntry($queue),
        isset($_GET['ajax']),
        Request::getVar('back') === 'admin',
        false,
        Request::getInt('max', null)
    );
} catch (InvalidArgumentException $e) {
    die('2');
}
