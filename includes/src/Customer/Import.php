<?php declare(strict_types=1);

namespace JTL\Customer;

use InvalidArgumentException;
use JTL\DB\DbInterface;
use JTL\Helpers\Text;
use JTL\Mail\Mail\Mail;
use JTL\Mail\Mailer;
use JTL\Services\JTL\PasswordServiceInterface;
use JTL\Shop;
use stdClass;

/**
 * Class Import
 * @package JTL\Customer
 */
class Import
{
    /**
     * @var array
     */
    private $format;

    /**
     * @var DbInterface
     */
    private $db;

    /**
     * @var int
     */
    private $customerGroupID = 1;

    /**
     * @var int
     */
    private $languageID = 1;

    /**
     * @var bool
     */
    private $generatePasswords = false;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var PasswordServiceInterface
     */
    private $passwordService;

    /**
     * @var string|null
     */
    private $defaultCountryCode;

    /**
     * Import constructor.
     * @param DbInterface $db
     * @param array|null  $format
     */
    public function __construct(DbInterface $db, array $format = null)
    {
        $this->db              = $db;
        $this->format          = $format ?? [
                'cKundenNr',
                'cPasswort',
                'cAnrede',
                'cTitel',
                'cVorname',
                'cNachname',
                'cFirma',
                'cZusatz',
                'cStrasse',
                'cHausnummer',
                'cAdressZusatz',
                'cPLZ',
                'cOrt',
                'cBundesland',
                'cLand',
                'cTel',
                'cMobil',
                'cFax',
                'cMail',
                'cUSTID',
                'cWWW',
                'fGuthaben',
                'cNewsletter',
                'dGeburtstag',
                'fRabatt',
                'cHerkunft',
                'dErstellt',
                'cAktiv'
            ];
        $this->passwordService = Shop::Container()->getPasswordService();
        $this->mailer          = Shop::Container()->get(Mailer::class);
        $this->initDefaultCountry();
    }

    /**
     * @param string $filename
     * @return array
     * @throws InvalidArgumentException
     */
    public function processFile(string $filename): array
    {
        $result = [];
        $file   = \fopen($filename, 'rb');
        if ($file === false) {
            throw new InvalidArgumentException('Cannot open file ' . $filename);
        }
        $delimiter = \getCsvDelimiter($filename);
        $row       = 0;
        $fmt       = [];
        while ($data = \fgetcsv($file, 2000, $delimiter, '"')) {
            if ($row === 0) {
                $fmt = $this->validate($data);
                if ($fmt === -1) {
                    $result[] = \__('errorFormatNotFound');
                    break;
                }
            } else {
                $result[] = \__('row') . ' ' . $row . ': ' . $this->processImport($fmt, $data);
            }
            $row++;
        }
        \fclose($file);

        return $result;
    }

    /**
     * @param array $data
     * @return array|int
     */
    protected function validate(array $data)
    {
        $fmt = [];
        $cnt = \count($data);
        for ($i = 0; $i < $cnt; $i++) {
            if (\in_array($data[$i], $this->format, true)) {
                $fmt[$i] = $data[$i];
            } else {
                $fmt[$i] = '';
            }
        }
        if ($this->generatePasswords === false) {
            if (!\in_array('cPasswort', $fmt, true) || !\in_array('cMail', $fmt, true)) {
                return -1;
            }
        } elseif (!\in_array('cMail', $fmt, true)) {
            return -1;
        }

        return $fmt;
    }

    /**
     * @param array $fmt
     * @param array $data
     * @return string
     */
    protected function processImport(array $fmt, array $data): string
    {
        $customer = $this->getCustomer();
        $cnt      = \count($data);
        for ($i = 0; $i < $cnt; $i++) {
            if (!empty($fmt[$i])) {
                $customer->{$fmt[$i]} = $data[$i];
            }
        }
        if (Text::filterEmailAddress($customer->cMail) === false) {
            return \sprintf(\__('errorInvalidEmail'), $customer->cMail);
        }
        if ($this->getGeneratePasswords() === false
            && (!$customer->cPasswort || $customer->cPasswort === 'd41d8cd98f00b204e9800998ecf8427e')
        ) {
            return \__('errorNoPassword');
        }
        if (!$customer->cNachname) {
            return \__('errorNoSurname');
        }

        $oldMail = $this->db->select('tkunde', 'cMail', $customer->cMail);
        if (isset($oldMail->kKunde) && $oldMail->kKunde > 0) {
            return \sprintf(\__('errorEmailDuplicate'), $customer->cMail);
        }
        if ($customer->cAnrede === 'f' || \mb_convert_case($customer->cAnrede, \MB_CASE_LOWER) === 'frau') {
            $customer->cAnrede = 'w';
        }
        if ($customer->cAnrede === 'h' || \mb_convert_case($customer->cAnrede, \MB_CASE_LOWER) === 'herr') {
            $customer->cAnrede = 'm';
        }
        if ($customer->cNewsletter === '1' || $customer->cNewsletter === 'y' || $customer->cNewsletter === 'Y') {
            $customer->cNewsletter = 'Y';
        } else {
            $customer->cNewsletter = 'N';
        }

        if (empty($customer->cLand) && $this->defaultCountryCode !== null) {
            $customer->cLand = $this->defaultCountryCode;
        }
        $password = '';
        if ($this->getGeneratePasswords() === true) {
            $password            = $this->passwordService->generate(\PASSWORD_DEFAULT_LENGTH);
            $customer->cPasswort = $this->passwordService->hash($password);
        }
        $tmp              = new stdClass();
        $tmp->cNachname   = $customer->cNachname;
        $tmp->cFirma      = $customer->cFirma;
        $tmp->cStrasse    = $customer->cStrasse;
        $tmp->cHausnummer = $customer->cHausnummer;
        $tmp->password    = $password;
        if ($customer->insertInDB()) {
            $this->notifyCustomer($customer, $tmp);

            return \__('successImportRecord') . $customer->cVorname . ' ' . $customer->cNachname;
        }

        return \__('errorImportRecord');
    }

    protected function initDefaultCountry(): void
    {
        $data = $this->db->getSingleObject(
            "SELECT cWert AS cLand 
                FROM teinstellungen 
                WHERE cName = 'kundenregistrierung_standardland'"
        );
        if ($data !== null && \mb_strlen($data->cLand) > 0) {
            $this->defaultCountryCode = $data->cLand;
        }
    }

    /**
     * @return Customer
     */
    private function getCustomer(): Customer
    {
        $customer                = new Customer();
        $customer->kKundengruppe = $this->getCustomerGroupID();
        $customer->kSprache      = $this->getLanguageID();
        $customer->cAbgeholt     = 'Y';
        $customer->cSperre       = 'N';
        $customer->cAktiv        = 'Y';
        $customer->nRegistriert  = 1;
        $customer->dErstellt     = 'NOW()';

        return $customer;
    }

    /**
     * @param Customer $customer
     * @param stdClass $tmp
     * @return bool
     */
    private function notifyCustomer(Customer $customer, stdClass $tmp): bool
    {
        if ($this->getGeneratePasswords() !== true) {
            return true;
        }
        $customer->cPasswortKlartext = $tmp->password;
        $customer->cNachname         = $tmp->cNachname;
        $customer->cFirma            = $tmp->cFirma;
        $customer->cStrasse          = $tmp->cStrasse;
        $customer->cHausnummer       = $tmp->cHausnummer;
        $obj                         = new stdClass();
        $obj->tkunde                 = $customer;
        $mail                        = new Mail();
        return $this->mailer->send($mail->createFromTemplateID(\MAILTEMPLATE_ACCOUNTERSTELLUNG_DURCH_BETREIBER, $obj));
    }

    /**
     * @return array
     */
    public function getFormat(): array
    {
        return $this->format;
    }

    /**
     * @param array $format
     */
    public function setFormat(array $format): void
    {
        $this->format = $format;
    }

    /**
     * @return int
     */
    public function getCustomerGroupID(): int
    {
        return $this->customerGroupID;
    }

    /**
     * @param int $customerGroupID
     */
    public function setCustomerGroupID(int $customerGroupID): void
    {
        $this->customerGroupID = $customerGroupID;
    }

    /**
     * @return int
     */
    public function getLanguageID(): int
    {
        return $this->languageID;
    }

    /**
     * @param int $languageID
     */
    public function setLanguageID(int $languageID): void
    {
        $this->languageID = $languageID;
    }

    /**
     * @return bool
     */
    public function getGeneratePasswords(): bool
    {
        return $this->generatePasswords;
    }

    /**
     * @param bool $generatePasswords
     */
    public function setGeneratePasswords(bool $generatePasswords): void
    {
        $this->generatePasswords = $generatePasswords;
    }

    /**
     * @return string|null
     */
    public function getDefaultCountryCode(): ?string
    {
        return $this->defaultCountryCode;
    }

    /**
     * @param string|null $defaultCountryCode
     */
    public function setDefaultCountryCode(?string $defaultCountryCode): void
    {
        $this->defaultCountryCode = $defaultCountryCode;
    }
}
