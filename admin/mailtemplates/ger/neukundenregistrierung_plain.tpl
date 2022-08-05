{includeMailTemplate template=header type=plain}

Guten Tag {$Kunde->cVorname} {$Kunde->cNachname},

vielen Dank für die Registrierung in unserem Onlineshop unter {$ShopURL}.

Zur Kontrolle hier noch einmal Ihre Kundendaten:

{$Kunde->cVorname} {$Kunde->cNachname}
{$Kunde->cStrasse} {$Kunde->cHausnummer}
{if $Kunde->cAdressZusatz}{$Kunde->cAdressZusatz}
{/if}{$Kunde->cPLZ} {$Kunde->cOrt}
{if $Kunde->cBundesland}{$Kunde->cBundesland}
{/if}{$Kunde->angezeigtesLand}
{if $Kunde->cTel}Tel.: {$Kunde->cTel|maskPrivate:2:4:'** ***'}
{/if}{if $Kunde->cMobil}Mobil: {$Kunde->cMobil|maskPrivate:2:4:'** ***'}
{/if}{if $Kunde->cFax}Fax: {$Kunde->cFax|maskPrivate:2:4:'** ***'}
{/if}
E-Mail-Adresse: {$Kunde->cMail}
{if $Kunde->cUSTID}Ust-ID: {$Kunde->cUSTID}
{/if}

Mit diesen Daten können Sie sich ab sofort in Ihrem persönlichen Kundenkonto anmelden und den aktuellen Status Ihrer Bestellungen verfolgen.

Wir freuen uns sehr, Sie als neuen Kunden bei uns begrüßen zu dürfen. Wenn Sie Fragen zu unserem Angebot oder speziellen Produkten haben, nehmen Sie einfach Kontakt mit uns auf.

Wir wünschen Ihnen viel Spaß beim Stöbern in unserem Sortiment.

Mit freundlichem Gruß
Ihr Team von {$Firma->cName}

{includeMailTemplate template=footer type=plain}