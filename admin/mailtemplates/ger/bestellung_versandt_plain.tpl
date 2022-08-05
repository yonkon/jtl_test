{includeMailTemplate template=header type=plain}

Guten Tag {$Kunde->cVorname} {$Kunde->cNachname},

Ihre Bestellung vom {$Bestellung->dErstelldatum_de} mit Bestellnummer {$Bestellung->cBestellNr} wurde heute an Sie versandt.

{foreach $Bestellung->oLieferschein_arr as $oLieferschein}
    {if $oLieferschein->oVersand_arr|count > 1}
        Mit den nachfolgenden Links können Sie sich über den Status Ihrer Sendungen informieren:
    {else}
        Mit dem nachfolgendem Link können Sie sich über den Status Ihrer Sendung informieren:
    {/if}

    {foreach $oLieferschein->oVersand_arr as $oVersand}
        {if $oVersand->getIdentCode()|strlen > 0}
            Tracking-URL: {$oVersand->getLogistikVarUrl()}
            {if $oVersand->getHinweis()|strlen > 0}
                Tracking-Hinweis: {$oVersand->getHinweis()}
            {/if}
        {/if}
    {/foreach}
{/foreach}

Wir wünschen Ihnen viel Spaß mit der Ware und bedanken uns für Ihren Einkauf und Ihr Vertrauen.

Mit freundlichem Gruß
Ihr Team von {$Firma->cName}

{includeMailTemplate template=footer type=plain}
