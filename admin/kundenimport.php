<?php declare(strict_types=1);

use JTL\Alert\Alert;
use JTL\Customer\Import;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('IMPORT_CUSTOMER_VIEW', true, true);

if (isset($_FILES['csv']['tmp_name'])
    && Request::postInt('kundenimport') === 1
    && Form::validateToken()
    && mb_strlen($_FILES['csv']['tmp_name']) > 0
) {
    $importer = new Import(Shop::Container()->getDB());
    $importer->setCustomerGroupID(Request::postInt('kKundengruppe'));
    $importer->setLanguageID(Request::postInt('kSprache'));
    $importer->setGeneratePasswords(Request::postInt('PasswortGenerieren') === 1);
    $result = $importer->processFile($_FILES['csv']['tmp_name']);
    $notice = '';
    foreach ($result as $item) {
        $notice .= $item . '<br>';
    }
    Shop::Container()->getAlertService()->addAlert(Alert::TYPE_NOTE, $notice, 'importNotice');
}
$smarty->assign('kundengruppen', Shop::Container()->getDB()->getObjects(
    'SELECT * FROM tkundengruppe ORDER BY cName'
))
    ->assign('step', $step ?? null)
    ->display('kundenimport.tpl');
