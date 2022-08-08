<?php
/** @var JTL\Smarty\ $smarty */

/** @var JTL\DB\NiceDB $db */
/** @var \JTL\Plugin\Plugin $plugin */

$redirects = $db->getObjects(
    '
SELECT t.*, tland.cDeutsch as name 
FROM landswitcher_redirect_url t
JOIN tland 
    ON tland.cISO = t.country_iso ',
);
$smarty->assign('redirects', $redirects);

echo $smarty->fetch($plugin->getPaths()->getAdminPath() . 'templates/list.tpl');
