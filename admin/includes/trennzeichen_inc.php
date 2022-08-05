<?php

use JTL\Catalog\Separator;
use JTL\Helpers\Text;
use JTL\Shop;

/**
 * @param array $post
 * @return bool
 */
function speicherTrennzeichen(array $post): bool
{
    $post = Text::filterXSS($post);
    foreach ([JTL_SEPARATOR_WEIGHT, JTL_SEPARATOR_AMOUNT, JTL_SEPARATOR_LENGTH] as $unit) {
        if (!isset($post['nDezimal_' . $unit], $post['cDezZeichen_' . $unit], $post['cTausenderZeichen_' . $unit])) {
            continue;
        }
        $trennzeichen = new Separator();
        $trennzeichen->setSprache((int)$_SESSION['editLanguageID'])
            ->setEinheit($unit)
            ->setDezimalstellen((int)$post['nDezimal_' . $unit])
            ->setDezimalZeichen($post['cDezZeichen_' . $unit])
            ->setTausenderZeichen($post['cTausenderZeichen_' . $unit]);
        $idx = 'kTrennzeichen_' . $unit;
        if (isset($post[$idx])) {
            $trennzeichen->setTrennzeichen((int)$post[$idx])
                ->update();
        } elseif (!$trennzeichen->save()) {
            return false;
        }
    }

    Shop::Container()->getCache()->flushTags(
        [CACHING_GROUP_CORE, CACHING_GROUP_CATEGORY, CACHING_GROUP_OPTION, CACHING_GROUP_ARTICLE]
    );

    return true;
}
