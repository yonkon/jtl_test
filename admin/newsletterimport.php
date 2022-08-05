<?php

use JTL\Alert\Alert;
use JTL\Helpers\Form;
use JTL\Helpers\Request;
use JTL\Helpers\Text;
use JTL\Newsletter\Newsletter;
use JTL\Optin\Optin;
use JTL\Optin\OptinNewsletter;
use JTL\Optin\OptinRefData;
use JTL\Shop;

require_once __DIR__ . '/includes/admininclude.php';
/** @global \JTL\Backend\AdminAccount $oAccount */
/** @global \JTL\Smarty\JTLSmarty $smarty */

$oAccount->permission('IMPORT_NEWSLETTER_RECEIVER_VIEW', true, true);
$alertHelper = Shop::Container()->getAlertService();
if (isset($_FILES['csv']['tmp_name'])
    && Request::postInt('newsletterimport') === 1
    && Form::validateToken()
    && mb_strlen($_FILES['csv']['tmp_name']) > 0
) {
    $file = fopen($_FILES['csv']['tmp_name'], 'r');
    if ($file !== false) {
        $format    = ['cAnrede', 'cVorname', 'cNachname', 'cEmail'];
        $row       = 0;
        $formatId  = -1;
        $fmt       = [];
        $importMsg = '';
        while ($data = fgetcsv($file, 2000, ';', '"')) {
            if ($row === 0) {
                $importMsg .= __('checkHead');
                $fmt        = checkformat($data, $format);
                if ($fmt === -1) {
                    $alertHelper->addAlert(Alert::TYPE_ERROR, __('errorFormatUnknown'), 'errorFormatUnknown');
                    break;
                }
                $importMsg .= '<br /><br />' . __('importPending') . '<br />';
            } else {
                $importMsg .= '<br />' . __('row') . $row . ': ' . processImport($fmt, $data);
            }
            $row++;
        }
        $alertHelper->addAlert(Alert::TYPE_NOTE, $importMsg, 'importMessage');
        fclose($file);
    }
}

$smarty->assign('kundengruppen', Shop::Container()->getDB()->getObjects(
    'SELECT * FROM tkundengruppe ORDER BY cName'
))
    ->display('newsletterimport.tpl');

/**
 * @param string $email
 * @return bool
 */
function checkBlacklist(string $email): bool
{
    $blacklist = Shop::Container()->getDB()->select(
        'tnewsletterempfaengerblacklist',
        'cMail',
        $email
    );

    return !empty($blacklist->cMail);
}

/**
 * @param array $data
 * @param array $formats
 * @return array|int
 */
function checkformat(array $data, array $formats)
{
    $fmt = [];
    $cnt = count($data);
    for ($i = 0; $i < $cnt; $i++) {
        if (!empty($data[$i]) && in_array($data[$i], $formats, true)) {
            $fmt[$i] = $data[$i];
        }
    }

    return in_array('cEmail', $fmt, true) ? $fmt : -1;
}

/**
 * @param array $fmt
 * @param array $data
 * @return string
 */
function processImport(array $fmt, array $data): string
{
    $recipient = new class
    {
        public $cAnrede;
        public $cEmail;
        public $cVorname;
        public $cNachname;
        public $kKunde = 0;
        public $kSprache;
        public $cOptCode;
        public $cLoeschCode;
        public $dEingetragen;
        public $nAktiv = 1;
    };
    $cnt       = count($fmt); // only columns that have no empty header jtl-shop/issues#296
    for ($i = 0; $i < $cnt; $i++) {
        if (!empty($fmt[$i])) {
            $recipient->{$fmt[$i]} = $data[$i];
        }
    }

    if (Text::filterEmailAddress($recipient->cEmail) === false) {
        return sprintf(__('errorEmailInvalid'), $recipient->cEmail);
    }
    if (checkBlacklist($recipient->cEmail)) {
        return sprintf(__('errorEmailInvalidBlacklist'), $recipient->cEmail);
    }
    if (!$recipient->cNachname) {
        return __('errorSurnameMissing');
    }
    $db       = Shop::Container()->getDB();
    $instance = new Newsletter($db, []);
    $oldMail  = $db->select('tnewsletterempfaenger', 'cEmail', $recipient->cEmail);
    if (isset($oldMail->kNewsletterEmpfaenger) && $oldMail->kNewsletterEmpfaenger > 0) {
        return sprintf(__('errorEmailExists'), $recipient->cEmail);
    }

    if ($recipient->cAnrede === 'f') {
        $recipient->cAnrede = 'Frau';
    }
    if ($recipient->cAnrede === 'm' || $recipient->cAnrede === 'h') {
        $recipient->cAnrede = 'Herr';
    }
    $recipient->cOptCode     = $instance->createCode('cOptCode', $recipient->cEmail);
    $recipient->cLoeschCode  = $instance->createCode('cLoeschCode', $recipient->cEmail);
    $recipient->dEingetragen = 'NOW()';
    $recipient->kSprache     = (int)$_POST['kSprache'];
    $recipient->kKunde       = 0;

    $customerData = $db->select('tkunde', 'cMail', $recipient->cEmail);
    if ($customerData !== null && $customerData->kKunde > 0) {
        $recipient->kKunde   = (int)$customerData->kKunde;
        $recipient->kSprache = (int)$customerData->kSprache;
    }
    $rowData               = new stdClass();
    $rowData->cAnrede      = $recipient->cAnrede;
    $rowData->cVorname     = $recipient->cVorname;
    $rowData->cNachname    = $recipient->cNachname;
    $rowData->kKunde       = $recipient->kKunde;
    $rowData->cEmail       = $recipient->cEmail;
    $rowData->dEingetragen = $recipient->dEingetragen;
    $rowData->kSprache     = $recipient->kSprache;
    $rowData->cOptCode     = $recipient->cOptCode;
    $rowData->cLoeschCode  = $recipient->cLoeschCode;
    $rowData->nAktiv       = $recipient->nAktiv;
    if ($db->insert('tnewsletterempfaenger', $rowData)) {
        unset($rowData->nAktiv);
        $rowData->cAktion = 'Daten-Import';
        $res              = $db->insert('tnewsletterempfaengerhistory', $rowData);

        try {
            $refData = (new OptinRefData())
                ->setSalutation($rowData->cAnrede ?? '')
                ->setFirstName($rowData->cVorname ?? '')
                ->setLastName($rowData->cNachname ?? '')
                ->setEmail($rowData->cEmail ?? '')
                ->setLanguageID(Shop::getLanguageID())
                ->setRealIP(Request::getRealIP());
            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            (new Optin(OptinNewsletter::class))
                ->getOptinInstance()
                ->bypassSendingPermission()
                ->createOptin($refData)
                ->activateOptin();
        } catch (\Exception $e) {
            Shop::Container()->getLogService()->notice('optin creation failed during import, for ' .
                $rowData->cEmail .
                ' cause: ' . $e->getMessage());
        }
        if ($res) {
            return __('successImport') .
                $recipient->cVorname .
                ' ' . $recipient->cNachname .
                ' (' . $recipient->cEmail . ')';
        }
    }

    return __('errorImportRow');
}
