<?php

use JTL\Shop;

/**
 * @param int   $customerGroupID
 * @param int   $languageID
 * @param array $post
 * @param int   $textID
 * @return bool
 */
function speicherAGBWRB(int $customerGroupID, int $languageID, array $post, int $textID = 0): bool
{
    if ($customerGroupID > 0 && $languageID > 0) {
        $item = new stdClass();
        if ($textID > 0) {
            Shop::Container()->getDB()->delete('ttext', 'kText', $textID);
            $item->kText = $textID;
        }
        $item->kSprache            = $languageID;
        $item->kKundengruppe       = $customerGroupID;
        $item->cAGBContentText     = $post['cAGBContentText'];
        $item->cAGBContentHtml     = $post['cAGBContentHtml'];
        $item->cWRBContentText     = $post['cWRBContentText'];
        $item->cWRBContentHtml     = $post['cWRBContentHtml'];
        $item->cDSEContentText     = $post['cDSEContentText'];
        $item->cDSEContentHtml     = $post['cDSEContentHtml'];
        $item->cWRBFormContentText = $post['cWRBFormContentText'];
        $item->cWRBFormContentHtml = $post['cWRBFormContentHtml'];
        /* deprecated */
        $item->nStandard = 0;

        Shop::Container()->getDB()->insert('ttext', $item);

        return true;
    }

    return false;
}
