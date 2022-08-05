<?php

use JTL\Alert\Alert;
use JTL\Helpers\Request;
use JTL\Pagination\Pagination;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('MODULE_GIFT_VIEW', true, true);
require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'gratisgeschenk_inc.php';

$settingsIDs = [
    'configgroup_10_gifts',
    'sonstiges_gratisgeschenk_nutzen',
    'sonstiges_gratisgeschenk_anzahl',
    'sonstiges_gratisgeschenk_sortierung'
];

if (Request::verifyGPCDataInt('einstellungen') === 1) {
    Shop::Container()->getAlertService()->addAlert(
        Alert::TYPE_SUCCESS,
        saveAdminSettings($settingsIDs, $_POST, [CACHING_GROUP_OPTION], true),
        'saveSettings'
    );
}
$paginationActive  = (new Pagination('aktiv'))
    ->setItemCount(gibAnzahlAktiverGeschenke())
    ->assemble();
$paginationCommon  = (new Pagination('haeufig'))
    ->setItemCount(gibAnzahlHaeufigGekaufteGeschenke())
    ->assemble();
$paginationLast100 = (new Pagination('letzte100'))
    ->setItemCount(gibAnzahlLetzten100Geschenke())
    ->assemble();

$smarty->assign('oPagiAktiv', $paginationActive)
    ->assign('oPagiHaeufig', $paginationCommon)
    ->assign('oPagiLetzte100', $paginationLast100)
    ->assign('oAktiveGeschenk_arr', holeAktiveGeschenke(' LIMIT ' . $paginationActive->getLimitSQL()))
    ->assign('oHaeufigGeschenk_arr', holeHaeufigeGeschenke(' LIMIT ' . $paginationCommon->getLimitSQL()))
    ->assign('oLetzten100Geschenk_arr', holeLetzten100Geschenke(' LIMIT ' . $paginationLast100->getLimitSQL()))
    ->assign('oConfig_arr', getAdminSectionSettings($settingsIDs, true))
    ->display('gratisgeschenk.tpl');
