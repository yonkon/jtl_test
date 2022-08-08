<?php
/** @var JTL\Smarty\ $smarty */

/** @var JTL\DB\NiceDB $db */
/** @var \JTL\Plugin\Plugin $plugin */

$countries = $db->selectAll('tland', [], [], 'cIso, cDeutsch');

$smarty->assign(
    'countries',
    array_map(function ($row) {
        return ['value' => $row->cIso, 'label' => $row->cDeutsch];
    }, $countries)
);


echo $smarty->fetch($plugin->getPaths()->getAdminPath() . 'templates/add.tpl');
