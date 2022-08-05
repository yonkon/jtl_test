{includeMailTemplate template=header type=plain}

Guten Tag {$Kunde->cVorname} {$Kunde->cNachname},

wir freuen uns, Ihnen mitteilen zu dürfen, dass auf Ihrem Kundenkonto ein Gutschein für Sie hinterlegt wurde.

Gutscheinwert: {$Gutschein->cLocalizedWert}

Grund für die Ausstellung des Gutscheins: {$Gutschein->cGrund}

Diesen Gutschein können Sie einfach bei Ihrer nächsten Bestellung einlösen. Der Betrag wird dann von Ihrem Einkaufswert abgezogen.

Viel Spaß bei Ihrem nächsten Einkauf in unserem Shop.

Mit freundlichem Gruß
Ihr Team von {$Firma->cName}

{includeMailTemplate template=footer type=plain}