<?php declare(strict_types=1);

namespace JTL\Export;

use Exception;
use JTL\Backend\AdminIO;
use JTL\Catalog\Category\Kategorie;
use JTL\DB\DbInterface;
use JTL\Session\Frontend;
use JTL\Shop;
use JTL\Smarty\ExportSmarty;
use SmartyException;
use stdClass;

/**
 * Class SyntaxChecker
 * @package JTL\Export
 */
class SyntaxChecker
{
    public const SYNTAX_FAIL        = 1;
    public const SYNTAX_NOT_CHECKED = -1;
    public const SYNTAX_OK          = 0;

    /**
     * @var ExportSmarty
     */
    private $smarty;

    /**
     * @var DbInterface
     */
    private $db;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    public $errorCode = self::SYNTAX_NOT_CHECKED;

    /**
     * SyntaxChecker constructor.
     * @param int         $id
     * @param DbInterface $db
     */
    public function __construct(int $id, DbInterface $db)
    {
        $this->id = $id;
        $this->db = $db;
    }

    /**
     * @param array $post
     * @param Model $model
     * @return array|Model
     */
    public function check(array $post, Model $model)
    {
        $validation = [];
        if (empty($post['cName'])) {
            $validation['cName'] = 1;
        } else {
            $model->setName($post['cName']);
        }
        $pathinfo           = \pathinfo(\PFAD_ROOT . \PFAD_EXPORT . $post['cDateiname']);
        $extensionWhitelist = \array_map('\strtolower', \explode(',', \EXPORTFORMAT_ALLOWED_FORMATS));
        $realpath           = \realpath($pathinfo['dirname']);
        if (empty($post['cDateiname'])) {
            $validation['cDateiname'] = 1;
        } elseif (\mb_strpos($post['cDateiname'], '.') === false) { // Dateiendung fehlt
            $validation['cDateiname'] = 2;
        } elseif ($realpath === false || \mb_strpos($realpath, \realpath(\PFAD_ROOT)) === false) {
            $validation['cDateiname'] = 3;
        } elseif (!\in_array(\mb_convert_case($pathinfo['extension'], \MB_CASE_LOWER), $extensionWhitelist, true)) {
            $validation['cDateiname'] = 4;
        } else {
            $model->setFilename($post['cDateiname']);
        }
        if (!isset($post['nSplitgroesse'])) {
            $post['nSplitgroesse'] = 0;
        }
        if (empty($post['cContent'])) {
            $validation['cContent'] = 1;
        } elseif (!\EXPORTFORMAT_ALLOW_PHP
            && (
                \mb_strpos($post['cContent'], '{php}') !== false
                || \mb_strpos($post['cContent'], '<?php') !== false
                || \mb_strpos($post['cContent'], '<%') !== false
                || \mb_strpos($post['cContent'], '<%=') !== false
                || \mb_strpos($post['cContent'], '<script language="php">') !== false
            )
        ) {
            $validation['cContent'] = 2;
        } else {
            $model->setContent(\str_replace('<tab>', "\t", $post['cContent']));
        }
        if (!isset($post['kSprache']) || (int)$post['kSprache'] === 0) {
            $validation['kSprache'] = 1;
        } else {
            $model->setLanguageID((int)$post['kSprache']);
        }
        if (!isset($post['kWaehrung']) || (int)$post['kWaehrung'] === 0) {
            $validation['kWaehrung'] = 1;
        } else {
            $model->setCurrencyID((int)$post['kWaehrung']);
        }
        if (!isset($post['kKundengruppe']) || (int)$post['kKundengruppe'] === 0) {
            $validation['kKundengruppe'] = 1;
        } else {
            $model->setCustomerGroupID((int)$post['kKundengruppe']);
        }
        if (\count($validation) > 0) {
            return $validation;
        }
        $model->setUseCache((int)$post['nUseCache']);
        $model->setVarcombOption((int)$post['nVarKombiOption']);
        $model->setSplitSize((int)$post['nSplitgroesse']);
        $model->setIsSpecial(0);
        $model->setEncoding($post['cKodierung']);
        $model->setPluginID((int)($post['kPlugin'] ?? 0));
        $model->setId((int)($post['kExportformat'] ?? 0));
        $model->setCampaignID((int)($post['kKampagne'] ?? 0));
        if (isset($post['cFusszeile'])) {
            $model->setFooter(\str_replace('<tab>', "\t", $post['cFusszeile']));
        }
        if (isset($post['cKopfzeile'])) {
            $model->setHeader(\str_replace('<tab>', "\t", $post['cKopfzeile']));
        }

        return $model;
    }

    /**
     * @param int $error
     * @return string
     */
    private static function getHTMLState(int $error): string
    {
        try {
            return Shop::Smarty()->assign('exportformat', (object)['nFehlerhaft' => $error])
                ->fetch('snippets/exportformat_state.tpl');
        } catch (SmartyException | Exception $e) {
            return '';
        }
    }

    /**
     * @param string $out
     * @param string $message
     * @return string
     */
    private static function stripMessage(string $out, string $message): string
    {
        $message = \strip_tags($message);
        // strip possible call stack
        if (\preg_match('/(Stack trace|Call Stack):/', $message, $hits)) {
            $callstackPos = \mb_strpos($message, $hits[0]);
            if ($callstackPos !== false) {
                $message = \mb_substr($message, 0, $callstackPos);
            }
        }
        $errText  = '';
        $fatalPos = \mb_strlen($out);
        // strip smarty output if fatal error occurs
        if (\preg_match('/((Recoverable )?Fatal error|Uncaught Error):/ui', $out, $hits)) {
            $fatalPos = \mb_strpos($out, $hits[0]);
            if ($fatalPos !== false) {
                $errText = \mb_substr($out, 0, $fatalPos);
            }
        }
        // strip possible error position from smarty output
        $errText = (string)\preg_replace('/[\t\n]/', ' ', \mb_substr($errText, 0, $fatalPos));
        $len     = \mb_strlen($errText);
        if ($len > 75) {
            $errText = '...' . \mb_substr($errText, $len - 75);
        }

        return \htmlentities($message) . ($len > 0 ? '<br/>on line: ' . \htmlentities($errText) : '');
    }

    private function initSmarty(): void
    {
        $this->smarty = new ExportSmarty($this->db);
        $this->smarty->assign('URL_SHOP', Shop::getURL())
            ->assign('Waehrung', Frontend::getCurrency())
            ->assign('Einstellungen', []);
    }

    /**
     * @return stdClass
     */
    private function doCheck(): stdClass
    {
        $res     = (object)[
            'result'  => 'ok',
            'message' => '',
        ];
        $session = new Session();
        $model   = Model::load(['id' => $this->id], $this->db);
        $session->initSession($model, $this->db);
        $this->initSmarty();
        $product     = null;
        $productData = $this->db->getSingleObject(
            "SELECT kArtikel 
                FROM tartikel 
                WHERE kVaterArtikel = 0 
                AND (cLagerBeachten = 'N' OR fLagerbestand > 0) LIMIT 1"
        );
        if ($productData !== null) {
            $product = new Product();
            $product->fuelleArtikel((int)$productData->kArtikel, Product::getExportOptions());
            $product->cDeeplink             = '';
            $product->Artikelbild           = '';
            $product->Lieferbar             = 'N';
            $product->Lieferbar_01          = 0;
            $product->cBeschreibungHTML     = '';
            $product->cKurzBeschreibungHTML = '';
            $product->fUst                  = 0.00;
            $product->Kategorie             = new Kategorie();
            $product->Kategoriepfad         = '';
            $product->Versandkosten         = -1;
        }
        try {
            $this->smarty->setErrorReporting(\E_ALL & ~\E_NOTICE & ~\E_STRICT & ~\E_DEPRECATED);
            $this->smarty->assign('Artikel', $product)
                ->fetch('db:' . $this->id);
            $this->updateError(self::SYNTAX_OK);
        } catch (Exception $e) {
            $this->updateError(self::SYNTAX_FAIL);
            $res->result  = 'fail';
            $res->message = \__($e->getMessage());
        }

        return $res;
    }

    /**
     * @param int $id
     * @return stdClass
     */
    public static function ioCheckSyntax(int $id): stdClass
    {
        \ini_set('html_errors', '0');
        \ini_set('display_errors', '1');
        \ini_set('log_errors', '0');
        \error_reporting(\E_ALL & ~\E_NOTICE & ~\E_STRICT & ~\E_DEPRECATED);

        Shop::Container()->getGetText()->loadAdminLocale('pages/exportformate');
        \register_shutdown_function(static function () use ($id) {
            $err = \error_get_last();
            if ($err !== null && ($err['type'] & !(\E_NOTICE | \E_STRICT | \E_DEPRECATED) !== 0)) {
                $out = \ob_get_clean();
                $res = (object)[
                    'result'  => 'fail',
                    'state'   => '<span class="label text-warning">' . \__('untested') . '</span>',
                    'message' => self::stripMessage($out, $err['message']),
                ];
                $ef  = new self($id, Shop::Container()->getDB());
                $ef->updateError(self::SYNTAX_FAIL);
                $res->state = self::getHTMLState(self::SYNTAX_FAIL);
                AdminIO::getInstance()->respondAndExit($res);
            }
        });

        $ef = new self($id, Shop::Container()->getDB());
        $ef->updateError(self::SYNTAX_NOT_CHECKED);

        try {
            $res = $ef->doCheck();
        } catch (Exception $e) {
            $res = (object)[
                'result'  => 'fail',
                'message' => \__($e->getMessage()),
            ];
        }
        $res->state = self::getHTMLState($ef->errorCode);

        return $res;
    }

    /**
     * @param int $error
     */
    public function updateError(int $error): void
    {
        if (Shop::getShopDatabaseVersion()->getMajor() < 5) {
            return;
        }
        if ($this->db->update(
            'texportformat',
            'kExportformat',
            $this->id,
            (object)['nFehlerhaft' => $error]
        ) !== false) {
            $this->errorCode = $error;
        }
    }
}
