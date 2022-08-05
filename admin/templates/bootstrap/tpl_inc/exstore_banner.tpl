{if $useExstoreWidgetBanner|default:false === true}
    <a href="{__('extensionStoreURL')}" target="_blank">
        <img src="gfx/exstore-banner-dashboard-{$language}.jpg"
             alt="Extensions entdecken!" class="exstore-banner">
    </a>
{else}
    <a href="{__('extensionStoreURL')}" target="_blank">
        <picture>
            <source media="(min-width: 768px)" srcset="gfx/exstore-banner-{$language}.jpg">
            <img src="gfx/exstore-banner-mobile-{$language}.jpg" alt="Extensions entdecken!" class="exstore-banner">
        </picture>
    </a>
{/if}