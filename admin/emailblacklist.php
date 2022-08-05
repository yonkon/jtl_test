<?php

use JTL\Alert\Alert;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('SETTINGS_EMAIL_BLACKLIST_VIEW', true, true);
$step = 'emailblacklist';
$db   = Shop::Container()->getDB();
if (Request::postInt('einstellungen') > 0) {
    Shop::Container()->getAlertService()->addAlert(
        Alert::TYPE_SUCCESS,
        saveAdminSectionSettings(CONF_EMAILBLACKLIST, $_POST),
        'saveSettings'
    );
}
if (Request::postInt('emailblacklist') === 1 && Form::validateToken()) {
    $addresses = explode(';', Text::filterXSS($_POST['cEmail']));
    if (count($addresses) > 0) {
        $db->query('TRUNCATE temailblacklist');
        foreach ($addresses as $mail) {
            $mail = strip_tags(trim($mail));
            if (mb_strlen($mail) > 0) {
                $db->insert('temailblacklist', (object)['cEmail' => $mail]);
            }
        }
    }
}
$blacklist = $db->selectAll('temailblacklist', [], []);
$blocked   = $db->getObjects(
    "SELECT *, DATE_FORMAT(dLetzterBlock, '%d.%m.%Y %H:%i') AS Datum
        FROM temailblacklistblock
        ORDER BY dLetzterBlock DESC
        LIMIT 100"
);

$smarty->assign('blacklist', $blacklist)
    ->assign('blocked', $blocked)
    ->assign('config', getAdminSectionSettings(CONF_EMAILBLACKLIST))
    ->assign('step', $step)
    ->display('emailblacklist.tpl');
