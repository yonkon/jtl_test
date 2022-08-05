<?php declare(strict_types=1);

use JTL\Consent\Admin;
use JTL\Consent\ConsentModel;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('CONTENT_PAGE_VIEW', true, true);

$admin = new Admin(
    new ConsentModel(),
    basename(__FILE__),
    Shop::Container()->getDB(),
    Shop::Container()->getAlertService()
);
$admin->handle();
$admin->display($smarty, 'consent.tpl');
