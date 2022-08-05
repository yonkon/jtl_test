<?php

namespace JTL\Customer;

use Exception;
use JTL\Helpers\GeneralObject;
use JTL\MainModel;
use JTL\Shop;
use stdClass;

/**
 * Class DataHistory
 * @package JTL\Customer
 */
class DataHistory extends MainModel
{
    /**
     * @var int
     */
    public $kKundendatenHistory;

    /**
     * @var int
     */
    public $kKunde;

    /**
     * @var string
     */
    public $cJsonAlt;

    /**
     * @var string
     */
    public $cJsonNeu;

    /**
     * @var string
     */
    public $cQuelle;

    /**
     * @var string
     */
    public $dErstellt;

    public const QUELLE_MEINKONTO = 'Mein Konto';

    public const QUELLE_BESTELLUNG = 'Bestellvorgang';

    public const QUELLE_DBES = 'Wawi Abgleich';

    /**
     * @return int
     */
    public function getKundendatenHistory(): int
    {
        return (int)$this->kKundendatenHistory;
    }

    /**
     * @param int $kKundendatenHistory
     * @return $this
     */
    public function setKundendatenHistory(int $kKundendatenHistory): self
    {
        $this->kKundendatenHistory = $kKundendatenHistory;

        return $this;
    }

    /**
     * @return int
     */
    public function getKunde(): int
    {
        return (int)$this->kKunde;
    }

    /**
     * @param int $kKunde
     * @return $this
     */
    public function setKunde(int $kKunde): self
    {
        $this->kKunde = $kKunde;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJsonAlt(): ?string
    {
        return $this->cJsonAlt;
    }

    /**
     * @param string $cJsonAlt
     * @return $this
     */
    public function setJsonAlt($cJsonAlt): self
    {
        $this->cJsonAlt = $cJsonAlt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getJsonNeu(): ?string
    {
        return $this->cJsonNeu;
    }

    /**
     * @param string $cJsonNeu
     * @return $this
     */
    public function setJsonNeu($cJsonNeu): self
    {
        $this->cJsonNeu = $cJsonNeu;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getQuelle(): ?string
    {
        return $this->cQuelle;
    }

    /**
     * @param string $cQuelle
     * @return $this
     */
    public function setQuelle($cQuelle): self
    {
        $this->cQuelle = $cQuelle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getErstellt(): ?string
    {
        return $this->dErstellt;
    }

    /**
     * @param string $dErstellt
     * @return $this
     */
    public function setErstellt($dErstellt): self
    {
        $this->dErstellt = (\mb_convert_case($dErstellt, \MB_CASE_UPPER) === 'NOW()')
            ? \date('Y-m-d H:i:s')
            : $dErstellt;

        return $this;
    }

    /**
     * @param int         $id
     * @param null|object $data
     * @param null        $option
     * @return $this
     */
    public function load($id, $data = null, $option = null)
    {
        $history = Shop::Container()->getDB()->select('tkundendatenhistory', 'kKundendatenHistory', $id);
        if (isset($history->kKundendatenHistory) && $history->kKundendatenHistory > 0) {
            $this->loadObject($history);
        }

        return $this;
    }

    /**
     * @param bool $bPrim
     * @return bool|int
     */
    public function save(bool $bPrim = true)
    {
        $ins = new stdClass();
        foreach (\array_keys(\get_object_vars($this)) as $member) {
            $ins->$member = $this->$member;
        }
        unset($ins->kKundendatenHistory);
        $kPrim = Shop::Container()->getDB()->insert('tkundendatenhistory', $ins);
        if ($kPrim > 0) {
            return $bPrim ? $kPrim : true;
        }

        return false;
    }

    /**
     * @return int
     * @throws Exception
     * @deprecated since 5.1.0
     */
    public function update(): int
    {
        \trigger_error(__METHOD__ . ' is deprecated.', \E_USER_DEPRECATED);
        $members = \array_keys(\get_object_vars($this));
        if (\is_array($members) && \count($members) > 0) {
            $upd = new stdClass();
            foreach ($members as $member) {
                $method = 'get' . \mb_substr($member, 1);
                if (\method_exists($this, $method)) {
                    $upd->$member = $this->$method();
                }
            }

            return Shop::Container()->getDB()->updateRow(
                'tkundendatenhistory',
                'kKundendatenHistory',
                $this->getKundendatenHistory(),
                $upd
            );
        }
        throw new Exception('ERROR: Object has no members!');
    }

    /**
     * @return int
     */
    public function delete(): int
    {
        return Shop::Container()->getDB()->delete(
            'tkundendatenhistory',
            'kKundendatenHistory',
            $this->getKundendatenHistory()
        );
    }

    /**
     * @param Customer $old
     * @param Customer $new
     * @param string   $source
     * @return bool
     */
    public static function saveHistory($old, $new, $source): bool
    {
        if (!\is_object($old) || !\is_object($new)) {
            return false;
        }
        if ($old->dGeburtstag === null) {
            $old->dGeburtstag = '';
        }
        if ($new->dGeburtstag === null) {
            $new->dGeburtstag = '';
        }

        $new->cPasswort = $old->cPasswort;

        if (Customer::isEqual($old, $new)) {
            return true;
        }
        $cryptoService = Shop::Container()->getCryptoService();
        $old           = GeneralObject::deepCopy($old);
        $new           = GeneralObject::deepCopy($new);
        // Encrypt Old
        $old->cNachname = $cryptoService->encryptXTEA(\trim($old->cNachname));
        $old->cFirma    = $cryptoService->encryptXTEA(\trim($old->cFirma));
        $old->cStrasse  = $cryptoService->encryptXTEA(\trim($old->cStrasse));
        // Encrypt New
        $new->cNachname = $cryptoService->encryptXTEA(\trim($new->cNachname));
        $new->cFirma    = $cryptoService->encryptXTEA(\trim($new->cFirma));
        $new->cStrasse  = $cryptoService->encryptXTEA(\trim($new->cStrasse));

        $history = new self();
        $history->setKunde($old->kKunde)
            ->setJsonAlt(\json_encode($old))
            ->setJsonNeu(\json_encode($new))
            ->setQuelle($source)
            ->setErstellt('NOW()');

        return $history->save() > 0;
    }
}
