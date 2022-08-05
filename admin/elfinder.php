<?php declare(strict_types=1);

use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Shop;

/**
 * @global \JTL\Smarty\JTLSmarty     $smarty
 * @global \JTL\Backend\AdminAccount $oAccount
 * @global string                    $currentTemplateDir
 */

require_once __DIR__ . '/includes/admininclude.php';
$oAccount->permission('IMAGE_UPLOAD', true, true);
require_once PFAD_ROOT . PFAD_ADMIN . PFAD_INCLUDES . 'elfinder_inc.php';

if (Form::validateToken()) {
    $mediafilesSubdir = STORAGE_OPC;
    $mediafilesType   = Request::verifyGPDataString('mediafilesType');
    $elfinderCommand  = Request::verifyGPDataString('cmd');
    $isCKEditor       = Request::verifyGPDataString('ckeditor') === '1';
    $CKEditorFuncNum  = Request::verifyGPDataString('CKEditorFuncNum');
    if ($mediafilesType === 'video') {
        $mediafilesSubdir = PFAD_MEDIA_VIDEO;
    }
    $mediafilesBaseUrlPath = parse_url(URL_SHOP . '/' . $mediafilesSubdir, PHP_URL_PATH);

    if (!empty($elfinderCommand)) {
        // Documentation for connector options:
        // https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
        // run elFinder
        $connector = new elFinderConnector(new elFinder([
            'bind'  => [
                'rm rename'      => static function ($cmd, &$result, $args, $elfinder, $volume) {
                    $sizes     = ['xs', 'sm', 'md', 'lg', 'xl'];
                    $fileTypes = ['jpeg', 'jpg', 'webp', 'png'];

                    foreach ($result['added'] as &$item) {
                        $item['name'] = mb_strtolower($item['name']);
                    }
                    unset($item);
                    foreach ($result['removed'] as $filename) {
                        foreach ($sizes as $size) {
                            $filePath   = str_replace(
                                PFAD_ROOT . PFAD_MEDIA_IMAGE . 'storage/opc/',
                                '',
                                $filename['realpath']
                            );
                            $scaledFile = PFAD_ROOT . PFAD_MEDIA_IMAGE . 'opc/' . $size . '/' . $filePath;
                            if (is_dir($scaledFile)) {
                                @rmdir($scaledFile);
                                continue;
                            }
                            $fileExtension = pathinfo($scaledFile, PATHINFO_EXTENSION);
                            $fileBaseName  = basename($scaledFile, '.' . $fileExtension);

                            foreach ($fileTypes as $fileType) {
                                $fileTemp = str_replace(
                                    $fileBaseName . '.' . $fileExtension,
                                    $fileBaseName . '.' . $fileType,
                                    $scaledFile
                                );
                                if (file_exists($fileTemp)) {
                                    @unlink($fileTemp);
                                }
                            }
                        }
                    }
                },
                'upload.presave' => static function (&$path, &$name, $tmpname, $_this, $volume) {
                    $name = mb_strtolower($name);
                },
            ],
            'roots' => [
                [
                    'tmbSize'       => 120,
                    'driver'        => 'LocalFileSystem',
                    'path'          => PFAD_ROOT . $mediafilesSubdir,
                    'URL'           => $mediafilesBaseUrlPath,
                    'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
                    'uploadDeny'    => ['all'],
                    'uploadAllow'   => ['image',
                        'video',
                        'text/plain',
                        'application/pdf',
                        'application/msword',
                        'application/excel',
                        'application/vnd.ms-excel',
                        'application/x-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ],
                    'uploadOrder'   => ['deny', 'allow'],
                    'accessControl' => 'access',
                ],
            ],
        ]));

        $connector->run();
    } else {
        $smarty->assign('mediafilesType', $mediafilesType)
            ->assign('mediafilesSubdir', $mediafilesSubdir)
            ->assign('isCKEditor', $isCKEditor)
            ->assign('CKEditorFuncNum', $CKEditorFuncNum)
            ->assign('templateUrl', Shop::getAdminURL() . '/' . $currentTemplateDir)
            ->assign('mediafilesBaseUrlPath', $mediafilesBaseUrlPath)
            ->display('elfinder.tpl');
    }
}
