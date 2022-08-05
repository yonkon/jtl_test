<?php
/**
 * This file is only intended to deliver HTML,
 * read from a Markdown-file,
 * via the jquery-function .load().
 *
 * Parameters are:
 * ('jtl_token': '', 'path': '')
 */

use JTL\Helpers\Form;
use JTL\Helpers\Text;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */

$oAccount->redirectOnFailure();

if (isset($_POST['path']) && Form::validateToken()) {
    $path  = realpath($_POST['path']);
    $base1 = realpath(PFAD_ROOT . PLUGIN_DIR);
    $base2 = realpath(PFAD_ROOT . PFAD_PLUGIN);
    if ($path !== false && (mb_strpos($path, $base1) === 0 || mb_strpos($path, $base2) === 0)) {
        $info = pathinfo($path);
        if (mb_convert_case($info['extension'], MB_CASE_LOWER) === 'md') {
            $parseDown      = new Parsedown();
            $licenseContent = mb_convert_encoding(
                $parseDown->text(Text::convertUTF8(file_get_contents($path))),
                'HTML-ENTITIES'
            );
            echo '<div class="markdown">' . $licenseContent . '</div>';
        }
    }
}
