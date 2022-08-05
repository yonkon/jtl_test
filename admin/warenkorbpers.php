<?php

use JTL\Alert\Alert;
use JTL\Cart\PersistentCart;
use JTL\Customer\Customer;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Pagination\Pagination;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('MODULE_SAVED_BASKETS_VIEW', true, true);

$step              = 'uebersicht';
$searchSQL         = new stdClass();
$searchSQL->cJOIN  = '';
$searchSQL->cWHERE = '';
$alertHelper       = Shop::Container()->getAlertService();

if (mb_strlen(Request::verifyGPDataString('cSuche')) > 0) {
    $query = Shop::Container()->getDB()->escape(Text::filterXSS(Request::verifyGPDataString('cSuche')));
    if (mb_strlen($query) > 0) {
        $searchSQL->cWHERE = " WHERE (tkunde.cKundenNr LIKE '%" . $query . "%'
            OR tkunde.cVorname LIKE '%" . $query . "%' 
            OR tkunde.cMail LIKE '%" . $query . "%')";
    }

    $smarty->assign('cSuche', $query);
}

if (Request::getInt('l') > 0 && Form::validateToken()) {
    $customerID = Request::getInt('l');
    $persCart   = new PersistentCart($customerID);
    if ($persCart->entferneSelf()) {
        $alertHelper->addAlert(Alert::TYPE_SUCCESS, __('successCartPersPosDelete'), 'successCartPersPosDelete');
    }

    unset($persCart);
}
$customerCount = (int)Shop::Container()->getDB()->getSingleObject(
    'SELECT COUNT(DISTINCT tkunde.kKunde) AS cnt
         FROM tkunde
         JOIN twarenkorbpers
             ON tkunde.kKunde = twarenkorbpers.kKunde
         JOIN twarenkorbperspos
             ON twarenkorbperspos.kWarenkorbPers = twarenkorbpers.kWarenkorbPers
         ' . $searchSQL->cWHERE
)->cnt;

$customerPagination = (new Pagination('kunden'))
    ->setItemCount($customerCount)
    ->assemble();

$customers = Shop::Container()->getDB()->getObjects(
    "SELECT tkunde.kKunde, tkunde.cFirma, tkunde.cVorname, tkunde.cNachname, 
        DATE_FORMAT(twarenkorbpers.dErstellt, '%d.%m.%Y  %H:%i') AS Datum, 
        COUNT(twarenkorbperspos.kWarenkorbPersPos) AS nAnzahl
        FROM tkunde
        JOIN twarenkorbpers 
            ON tkunde.kKunde = twarenkorbpers.kKunde
        JOIN twarenkorbperspos 
            ON twarenkorbperspos.kWarenkorbPers = twarenkorbpers.kWarenkorbPers
        " . $searchSQL->cWHERE . '
        GROUP BY tkunde.kKunde
        ORDER BY twarenkorbpers.dErstellt DESC
        LIMIT ' . $customerPagination->getLimitSQL()
);

foreach ($customers as $item) {
    $customer = new Customer((int)$item->kKunde);

    $item->cNachname = $customer->cNachname;
    $item->cFirma    = $customer->cFirma;
}

$smarty->assign('oKunde_arr', $customers)
    ->assign('oPagiKunden', $customerPagination);

if (Request::getInt('a') > 0) {
    $step           = 'anzeigen';
    $customerID     = Request::getInt('a');
    $persCartCount  = (int)Shop::Container()->getDB()->getSingleObject(
        'SELECT COUNT(*) AS cnt
            FROM twarenkorbperspos
            JOIN twarenkorbpers 
                ON twarenkorbpers.kWarenkorbPers = twarenkorbperspos.kWarenkorbPers
            WHERE twarenkorbpers.kKunde = :cid',
        ['cid' => $customerID]
    )->cnt;
    $cartPagination = (new Pagination('warenkorb'))
        ->setItemCount($persCartCount)
        ->assemble();

    $carts = Shop::Container()->getDB()->getObjects(
        "SELECT tkunde.kKunde AS kKundeTMP, tkunde.cVorname, tkunde.cNachname, twarenkorbperspos.kArtikel, 
            twarenkorbperspos.cArtikelName, twarenkorbpers.kKunde, twarenkorbperspos.fAnzahl, 
            DATE_FORMAT(twarenkorbperspos.dHinzugefuegt, '%d.%m.%Y  %H:%i') AS Datum
            FROM twarenkorbpers
            JOIN tkunde 
                ON tkunde.kKunde = twarenkorbpers.kKunde
            JOIN twarenkorbperspos 
                ON twarenkorbpers.kWarenkorbPers = twarenkorbperspos.kWarenkorbPers
            WHERE twarenkorbpers.kKunde = :cid
            LIMIT " . $cartPagination->getLimitSQL(),
        ['cid' => $customerID]
    );
    foreach ($carts as $cart) {
        $customer = new Customer((int)$cart->kKundeTMP);

        $cart->cNachname = $customer->cNachname;
        $cart->cFirma    = $customer->cFirma;
    }

    $smarty->assign('oWarenkorbPersPos_arr', $carts)
        ->assign('kKunde', $customerID)
        ->assign('oPagiWarenkorb', $cartPagination);
}

$smarty->assign('step', $step)
    ->display('warenkorbpers.tpl');
