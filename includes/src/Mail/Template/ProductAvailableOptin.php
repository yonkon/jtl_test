<?php declare(strict_types=1);

namespace JTL\Mail\Template;

use JTL\Smarty\JTLSmarty;

/**
 * Class ProductAvailableOptin
 * @package JTL\Mail\Template
 */
class ProductAvailableOptin extends ProductTemplate
{
    protected $id = \MAILTEMPLATE_PRODUKT_WIEDER_VERFUEGBAR_OPTIN;

    /**
     * @inheritdoc
     */
    public function preRender(JTLSmarty $smarty, $data): void
    {
        parent::preRender($smarty, $data);
        if ($data === null) {
            return;
        }
        $data = $this->useOriginalName($data);
        $smarty->assign('Benachrichtigung', $data->tverfuegbarkeitsbenachrichtigung)
               ->assign('Artikel', $data->tartikel)
               ->assign('Optin', $data->optin)
               ->assign('Receiver', $data->mailReceiver);
    }
}
