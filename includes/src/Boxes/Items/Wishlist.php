<?php declare(strict_types=1);

namespace JTL\Boxes\Items;

use JTL\Catalog\Product\Preise;
use JTL\Helpers\Text;
use JTL\Session\Frontend;
use JTL\Shop;

/**
 * Class Wishlist
 * @package JTL\Boxes\Items
 */
final class Wishlist extends AbstractBox
{
    /**
     * @var int
     */
    private $wishListID = 0;

    /**
     * Wishlist constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->addMapping('nBilderAnzeigen', 'ShowImages');
        $this->addMapping('CWunschlistePos_arr', 'Items');
        $this->setShow($config['global']['global_wunschliste_anzeigen'] === 'Y');
        if (!empty(Frontend::getWishList()->kWunschliste)) {
            $this->setWishListID(Frontend::getWishList()->kWunschliste);
            $requestURI       = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
            $additionalParams = [];
            $parsed           = \parse_url($requestURI);
            $shopURL          = Shop::getURL() . ($parsed['path'] ?? '/') . '?';
            if (isset($parsed['query'])) {
                \parse_str($parsed['query'], $additionalParams);
            }
            $wishlistItems = Frontend::getWishList()->CWunschlistePos_arr;
            $validPostVars = ['a', 'k', 's', 'h', 'l', 'm', 't', 'hf', 'kf', 'qf', 'show', 'suche'];
            $postMembers   = \array_keys($_REQUEST);
            foreach ($postMembers as $postMember) {
                if ((int)$_REQUEST[$postMember] > 0 && \in_array($postMember, $validPostVars, true)) {
                    $additionalParams[$postMember] = (int)$_REQUEST[$postMember];
                }
            }
            $additionalParams = Text::filterXSS($additionalParams);
            foreach ($wishlistItems as $wishlistItem) {
                $additionalParams['wlplo'] = $wishlistItem->kWunschlistePos;
                $wishlistItem->cURL        = $shopURL . \http_build_query($additionalParams);
                if (Frontend::getCustomerGroup()->isMerchant()) {
                    $price = isset($wishlistItem->Artikel->Preise->fVKNetto)
                        ? (int)$wishlistItem->fAnzahl * $wishlistItem->Artikel->Preise->fVKNetto
                        : 0;
                } else {
                    $price = isset($wishlistItem->Artikel->Preise->fVKNetto)
                        ? (int)$wishlistItem->fAnzahl * ($wishlistItem->Artikel->Preise->fVKNetto *
                            (100 + $_SESSION['Steuersatz'][$wishlistItem->Artikel->kSteuerklasse]) / 100)
                        : 0;
                }
                $wishlistItem->cPreis = Preise::getLocalizedPriceString($price, Frontend::getCurrency());
            }
            $this->setItemCount((int)$this->config['boxen']['boxen_wunschzettel_anzahl']);
            $this->setItems(\array_reverse($wishlistItems));
        }
        \executeHook(\HOOK_BOXEN_INC_WUNSCHZETTEL, ['box' => $this]);
    }

    /**
     * @return int
     */
    public function getWishListID(): int
    {
        return $this->wishListID;
    }

    /**
     * @param int $id
     */
    public function setWishListID(int $id): void
    {
        $this->wishListID = $id;
    }

    /**
     * @return bool
     */
    public function getShowImages(): bool
    {
        return $this->config['boxen']['boxen_wunschzettel_bilder'] === 'Y';
    }

    /**
     * @param string $value
     */
    public function setShowImages($value): void
    {
    }
}
