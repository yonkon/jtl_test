<?php declare(strict_types=1);

namespace JTL\Optin;

use JTL\Alert\Alert;
use JTL\Campaign;
use JTL\Catalog\Product\Artikel;
use JTL\DB\ReturnType;
use JTL\Helpers\Request;
use JTL\Mail\Mail\Mail;
use JTL\Mail\Mailer;
use JTL\Session\Frontend;
use JTL\Shop;
use stdClass;

/**
 * Class OptinAvailAgain
 * @package JTL\Optin
 */
class OptinAvailAgain extends OptinBase implements OptinInterface
{
    /**
     * @var Artikel
     */
    private $product;

    /**
     * OptinAvailAgain constructor.
     * @param parent $inheritData
     */
    public function __construct($inheritData)
    {
        [
            $this->dbHandler,
            $this->nowDataTime,
            $this->refData,
            $this->emailAddress,
            $this->optCode,
            $this->actionPrefix
        ] = $inheritData;
    }

    /**
     * @param OptinRefData $refData
     * @param int $location
     * @return OptinInterface
     * @throws \JTL\Exceptions\CircularReferenceException
     * @throws \JTL\Exceptions\ServiceNotFoundException
     */
    public function createOptin(OptinRefData $refData, int $location = 0): OptinInterface
    {
        $this->refData                       = $refData;
        $options                             = Artikel::getDefaultOptions();
        $options->nKeineSichtbarkeitBeachten = 1;
        $this->product                       = (new Artikel())->fuelleArtikel($this->refData->getProductId(), $options);
        $this->saveOptin($this->generateUniqOptinCode());

        return $this;
    }

    /**
     * send the optin activation mail
     */
    public function sendActivationMail(): void
    {
        $customerId = Frontend::getCustomer()->getID();

        $recipient               = new stdClass();
        $recipient->kSprache     = Shop::getLanguageID();
        $recipient->kKunde       = $customerId;
        $recipient->nAktiv       = $customerId > 0;
        $recipient->cAnrede      = $this->refData->getSalutation();
        $recipient->cVorname     = $this->refData->getFirstName();
        $recipient->cNachname    = $this->refData->getLastName();
        $recipient->cEmail       = $this->refData->getEmail();
        $recipient->dEingetragen = $this->nowDataTime->format('Y-m-d H:i:s');

        $optin                  = new stdClass();
        $productURL             = Shop::getURL() . '/' . $this->product->cSeo;
        $optinCodePrefix        = '?oc=';
        $optin->activationURL   = $productURL . $optinCodePrefix . self::ACTIVATE_CODE . $this->optCode;
        $optin->deactivationURL = $productURL . $optinCodePrefix . self::DELETE_CODE . $this->optCode;

        $templateData                                   = new stdClass();
        $templateData->tkunde                           = $_SESSION['Kunde'] ?? null;
        $templateData->tartikel                         = $this->product;
        $templateData->tverfuegbarkeitsbenachrichtigung = [];
        $templateData->optin                            = $optin;
        $templateData->mailReceiver                     = $recipient;

        $mailer = Shop::Container()->get(Mailer::class);
        $mail   = new Mail();
        $mailer->send($mail->createFromTemplateID(\MAILTEMPLATE_PRODUKT_WIEDER_VERFUEGBAR_OPTIN, $templateData));

        Shop::Container()->getAlertService()->addAlert(
            Alert::TYPE_INFO,
            Shop::Lang()->get('availAgainOptinCreated', 'messages'),
            'availAgainOptinCreated'
        );
    }

    /**
     * @throws \Exception
     */
    public function activateOptin(): void
    {
        $data            = new stdClass();
        $data->kSprache  = Shop::getLanguageID();
        $data->cIP       = Request::getRealIP();
        $data->dErstellt = 'NOW()';
        $data->nStatus   = 0;
        $data->kArtikel  = $this->refData->getProductId();
        $data->cMail     = $this->refData->getEmail();
        $data->cVorname  = $this->refData->getFirstName();
        $data->cNachname = $this->refData->getLastName();

        \executeHook(\HOOK_ARTIKEL_INC_BENACHRICHTIGUNG, ['Benachrichtigung' => $data]);

        $inquiryID = $this->dbHandler->queryPrepared(
            'INSERT INTO tverfuegbarkeitsbenachrichtigung
                (cVorname, cNachname, cMail, kSprache, kArtikel, cIP, dErstellt, nStatus)
                VALUES
                (:cVorname, :cNachname, :cMail, :kSprache, :kArtikel, :cIP, NOW(), :nStatus)
                ON DUPLICATE KEY UPDATE
                    cVorname = :cVorname, cNachname = :cNachname, ksprache = :kSprache,
                    cIP = :cIP, dErstellt = NOW(), nStatus = :nStatus',
            \get_object_vars($data),
            ReturnType::LAST_INSERTED_ID
        );
        if (isset($_SESSION['Kampagnenbesucher'])) {
            Campaign::setCampaignAction(\KAMPAGNE_DEF_VERFUEGBARKEITSANFRAGE, $inquiryID, 1.0);
        }
    }

    /**
     * do opt-in specific de-activations
     */
    public function deactivateOptin(): void
    {
        $this->dbHandler->delete('tverfuegbarkeitsbenachrichtigung', 'cMail', $this->refData->getEmail());
    }

    /**
     * @return Artikel
     */
    public function getProduct(): Artikel
    {
        return $this->product;
    }

    /**
     * @param Artikel $product
     * @return OptinAvailAgain
     */
    public function setProduct(Artikel $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * load a optin-tupel, via email and productID
     * restore its reference data
     */
    protected function loadOptin(): void
    {
        $refData = $this->dbHandler->getObjects(
            'SELECT *
              FROM toptin
              WHERE cMail = :mail
                AND kOptinClass = :optinclass',
            [
                'mail'       => $this->emailAddress,
                'optinclass' => \get_class($this)
            ]
        );
        foreach ($refData as $optin) {
            /** @var OptinRefData $refData */
            $refData = \unserialize($optin->cRefData, ['OptinRefData']);
            if ($refData->getProductId() === $this->getProduct()->kArtikel) {
                $this->foundOptinTupel = $optin;
            }
        }
    }
}
