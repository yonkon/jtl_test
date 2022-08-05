<?php

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Smarty\JTLSmarty $smarty */
/** @global \JTL\Backend\AdminAccount $oAccount */

$oAccount->permission('FILESYSTEM_VIEW', true, true);

use JTL\Alert\Alert;
use JTL\Filesystem\AdapterFactory;
use JTL\Filesystem\Filesystem;
use JTL\Helpers\Form;
use JTL\Helpers\Text;
use JTL\Shop;
use JTL\Shopsetting;

$shopSettings = Shopsetting::getInstance();
$alertHelper  = Shop::Container()->getAlertService();

Shop::Container()->getGetText()->loadConfigLocales(true, true);

if (!empty($_POST) && Form::validateToken()) {
    $postData = Text::filterXSS($_POST);
    $alertHelper->addAlert(Alert::TYPE_SUCCESS, saveAdminSectionSettings(CONF_FS, $_POST), 'saveSettings');
    $shopSettings->reset();

    if (isset($postData['test'])) {
        try {
            $config  = Shop::getSettings([CONF_FS])['fs'];
            $factory = new AdapterFactory($config);
            $factory->setFtpConfig([
                'ftp_host'     => $postData['ftp_hostname'],
                'ftp_port'     => (int)($postData['ftp_port'] ?? 21),
                'ftp_username' => $postData['ftp_user'],
                'ftp_password' => $postData['ftp_pass'],
                'ftp_ssl'      => (int)$postData['ftp_ssl'] === 1,
                'ftp_root'     => $postData['ftp_path']
            ]);
            $factory->setSftpConfig([
                'sftp_host'     => $postData['sftp_hostname'],
                'sftp_port'     => (int)($postData['sftp_port'] ?? 22),
                'sftp_username' => $postData['sftp_user'],
                'sftp_password' => $postData['sftp_pass'],
                'sftp_privkey'  => $postData['sftp_privkey'],
                'sftp_root'     => $postData['sftp_path']
            ]);
            $factory->setAdapter($postData['fs_adapter']);
            $fs         = new Filesystem($factory->getAdapter());
            $isShopRoot = $fs->fileExists('includes/config.JTL-Shop.ini.php');
            if ($isShopRoot) {
                $alertHelper->addAlert(Alert::TYPE_INFO, __('fsValidConnection'), 'fsValidConnection');
            } else {
                $alertHelper->addAlert(Alert::TYPE_ERROR, __('fsInvalidShopRoot'), 'fsInvalidShopRoot');
            }
        } catch (Exception $e) {
            $alertHelper->addAlert(Alert::TYPE_ERROR, $e->getMessage(), 'errorFS');
        }
    }
}
$config = getAdminSectionSettings(CONF_FS);
Shop::Container()->getGetText()->localizeConfigs($config);
$smarty->assign('oConfig_arr', $config)
    ->display('filesystem.tpl');
