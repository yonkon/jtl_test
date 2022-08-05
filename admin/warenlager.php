<?php

use JTL\Alert\Alert;
use JTL\Catalog\Warehouse;
use JTL\Helpers\Form;
use JTL\Helpers\GeneralObject;
use JTL\Helpers\Text;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('WAREHOUSE_VIEW', true, true);
$step        = 'uebersicht';
$postData    = Text::filterXSS($_POST);
$action      = (isset($postData['a']) && Form::validateToken()) ? $postData['a'] : null;
$alertHelper = Shop::Container()->getAlertService();
$db          = Shop::Container()->getDB();

if ($action === 'update') {
    $db->query('UPDATE twarenlager SET nAktiv = 0');
    if (GeneralObject::hasCount('kWarenlager', $postData)) {
        $wl = array_map('\intval', $postData['kWarenlager']);
        $db->query('UPDATE twarenlager SET nAktiv = 1 WHERE kWarenlager IN (' . implode(', ', $wl) . ')');
    }
    if (GeneralObject::hasCount('cNameSprache', $postData)) {
        foreach ($postData['cNameSprache'] as $kWarenlager => $assocLang) {
            $db->delete('twarenlagersprache', 'kWarenlager', (int)$kWarenlager);
            foreach ($assocLang as $languageID => $name) {
                if (mb_strlen(trim($name)) > 1) {
                    $data              = new stdClass();
                    $data->kWarenlager = (int)$kWarenlager;
                    $data->kSprache    = (int)$languageID;
                    $data->cName       = htmlspecialchars(trim($name), ENT_COMPAT | ENT_HTML401, JTL_CHARSET);

                    $db->insert('twarenlagersprache', $data);
                }
            }
        }
    }
    Shop::Container()->getCache()->flushTags([CACHING_GROUP_ARTICLE]);
    $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successStoreRefresh'), 'successStoreRefresh');
}

if ($step === 'uebersicht') {
    $smarty->assign('warehouses', Warehouse::getAll(false, true));
}

$smarty->assign('step', $step)
    ->display('warenlager.tpl');
