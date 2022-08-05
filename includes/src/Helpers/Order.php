<?php

namespace JTL\Helpers;

use JTL\Cart\Cart;
use JTL\Cart\CartHelper;
use JTL\Catalog\Currency;
use JTL\Catalog\Product\Preise;
use JTL\Checkout\Bestellung;
use JTL\Checkout\Lieferadresse;
use JTL\Checkout\Rechnungsadresse;
use JTL\Customer\Customer;
use JTL\Customer\CustomerGroup;
use JTL\Session\Frontend;
use JTL\Shop;
use stdClass;

/**
 * Class Order
 * @package JTL\Helpers
 */
class Order extends CartHelper
{
    /**
     * @var Bestellung
     */
    protected $order;

    /**
     * Order constructor.
     * @param Bestellung $order
     */
    public function __construct(Bestellung $order)
    {
        $this->order = $order;
    }

    /**
     * @inheritDoc
     */
    protected function calculateCredit(stdClass $cartInfo): void
    {
        if ((float)$this->order->fGuthaben !== 0.0) {
            $amountGross = $this->order->fGuthaben;

            $cartInfo->discount[self::NET]   += $amountGross;
            $cartInfo->discount[self::GROSS] += $amountGross;
        }
        // positive discount
        $cartInfo->discount[self::NET]   *= -1;
        $cartInfo->discount[self::GROSS] *= -1;
    }

    /**
     * @return Cart|Bestellung|null
     */
    public function getObject()
    {
        return $this->order;
    }

    /**
     * @return Lieferadresse|Rechnungsadresse
     */
    public function getShippingAddress()
    {
        if ((int)$this->order->kLieferadresse > 0 && \is_object($this->order->Lieferadresse)) {
            return $this->order->Lieferadresse;
        }

        return $this->getBillingAddress();
    }

    /**
     * @return Rechnungsadresse|null
     */
    public function getBillingAddress(): ?Rechnungsadresse
    {
        return $this->order->oRechnungsadresse;
    }

    /**
     * @inheritDoc
     */
    public function getPositions(): array
    {
        return $this->order->Positionen;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): ?Customer
    {
        return $this->order->oKunde;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerGroup(): CustomerGroup
    {
        return new CustomerGroup($this->order->oKunde->getGroupID());
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->order->Waehrung;
    }

    /**
     * @return string iso
     */
    public function getLanguage(): string
    {
        return Shop::Lang()->getIsoFromLangID($this->order->kSprache)->cISO;
    }

    /**
     * @return string
     */
    public function getInvoiceID(): string
    {
        return $this->order->cBestellNr;
    }

    /**
     * @return int
     */
    public function getIdentifier(): int
    {
        return (int)$this->order->kBestellung;
    }

    /**
     * @param int $customerID
     * @return object|null
     * @since 5.0.0
     */
    public static function getLastOrderRefIDs(int $customerID): ?object
    {
        $order = Shop::Container()->getDB()->getSingleObject(
            'SELECT kBestellung, kWarenkorb, kLieferadresse, kRechnungsadresse, kZahlungsart, kVersandart
                FROM tbestellung
                WHERE kKunde = :customerID
                ORDER BY dErstellt DESC
                LIMIT 1',
            ['customerID' => $customerID]
        );

        return $order !== null
            ? (object)[
                'kBestellung'       => (int)$order->kBestellung,
                'kWarenkorb'        => (int)$order->kWarenkorb,
                'kLieferadresse'    => (int)$order->kLieferadresse,
                'kRechnungsadresse' => (int)$order->kRechnungsadresse,
                'kZahlungsart'      => (int)$order->kZahlungsart,
                'kVersandart'       => (int)$order->kVersandart,
            ]
            : (object)[
                'kBestellung'       => 0,
                'kWarenkorb'        => 0,
                'kLieferadresse'    => 0,
                'kRechnungsadresse' => 0,
                'kZahlungsart'      => 0,
                'kVersandart'       => 0,
            ];
    }

    /**
     * @param stdClass|Bestellung|null $order
     * @return float
     * @since 5.1.0
     */
    public static function getOrderCredit($order = null): float
    {
        $customer  = Frontend::getCustomer();
        $cartTotal = (float)Frontend::getCart()->gibGesamtsummeWaren(true, false);
        $credit    = \min((float)$customer->fGuthaben, $cartTotal);

        \executeHook(\HOOK_BESTELLUNG_SETZEGUTHABEN, [
            'creditToUse'    => &$credit,
            'cartTotal'      => $cartTotal,
            'customerCredit' => (float)$customer->fGuthaben
        ]);

        if ($order !== null) {
            $order->fGuthabenGenutzt   = $credit;
            $order->GutscheinLocalized = Preise::getLocalizedPriceString($credit);
        }

        return $credit;
    }
}
