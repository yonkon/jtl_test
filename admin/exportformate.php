<?php declare(strict_types=1);

use JTL\Export\Admin;
use JTL\Shop;

/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

require_once __DIR__ . '/includes/admininclude.php';
require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'exportformat_inc.php';

Shop::Container()->getGetText()->loadConfigLocales(true, true);

$oAccount->permission('EXPORT_FORMATS_VIEW', true, true);
Shop::Container()->getCache()->flushTags([Status::CACHE_ID_EXPORT_SYNTAX_CHECK]);

/** @global \JTL\Smarty\JTLSmarty $smarty */

$admin = new Admin(Shop::Container()->getDB(), Shop::Container()->getAlertService(), $smarty);

$admin->getAction();
$admin->display();
