<?php

use JTL\Backend\AdminFavorite;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Shop;

/**
 * @global \JTL\Smarty\JTLSmarty     $smarty
 * @global \JTL\Backend\AdminAccount $oAccount
 */

require_once __DIR__ . '/includes/admininclude.php';
$oAccount->redirectOnFailure();

$adminID = Shop::Container()->getAdminAccount()->getID();
if (isset($_POST['title'], $_POST['url'])
    && Form::validateToken()
    && Request::verifyGPDataString('action') === 'save'
) {
    $titles = Text::filterXSS($_POST['title']);
    $urls   = Text::filterXSS($_POST['url']);
    if (is_array($titles) && is_array($urls) && count($titles) === count($urls)) {
        AdminFavorite::remove($adminID);
        foreach ($titles as $i => $title) {
            AdminFavorite::add($adminID, $title, $urls[$i], $i);
        }
    }
}

$smarty->assign('favorites', $oAccount->favorites())
    ->display('favs.tpl');
