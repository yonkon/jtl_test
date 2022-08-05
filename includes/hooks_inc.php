<?php

use Illuminate\Support\Collection;
use JTL\Cart\Cart;
use JTL\Cart\CartItem;
use JTL\Catalog\Category\Kategorie;
use JTL\Catalog\Hersteller;
use JTL\Catalog\Product\Artikel;
use JTL\Catalog\Product\Bewertung;
use JTL\Catalog\Product\Merkmal;
use JTL\Catalog\Product\MerkmalWert;
use JTL\Catalog\Product\Preise;
use JTL\CheckBox;
use JTL\Checkout\Bestellung;
use JTL\Checkout\Versandart;
use JTL\Customer\Customer;
use JTL\Emailvorlage;
use JTL\Firma;
use JTL\IO\IO;

/**
 * Ende Artikeldetail
 *
 * @file artikel.php
 * @param Artikel - oArtikel
 */
define('HOOK_ARTIKEL_PAGE', 1);

/**
 * Falls nicht wahrend Bestellung bezahlt wird
 *
 * @file bestellabschluss.php
 * @param Bestellung - oBestellung
 */
define('HOOK_BESTELLABSCHLUSS_PAGE', 2);

/**
 * Falls während Bestellung bezahlt wird
 *
 * @file bestellabschluss.php
 * @param Bestellung - oBestellung
 */
define('HOOK_BESTELLABSCHLUSS_PAGE_ZAHLUNGSVORGANG', 3);

/**
 * Accountwahl im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPACCOUNTWAHL', 4);

/**
 * Unregistriert bestellen im Bestellvorgang (Formular)
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPUNREGISTRIERTBESTELLEN', 5);

/**
 * Auswahl der Lieferadresse im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPLIEFERADRESSE', 6);

/**
 * Auswahl der Versandart im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPVERSAND', 7);

/**
 * Auswahl der Zahlungsart im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPZAHLUNG', 8);

/**
 * Auswahl der Zahlungsart mit Zusatzschritt im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPZAHLUNGZUSATZSCHRITT', 9);

/**
 * Übersichtsseite im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPBESTAETIGUNG', 10);

/**
 * Plausibilitätsprüfung nach Wahl der Versandart
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPVERSAND_PLAUSI', 11);

/**
 * Plausibilitätsprüfung nach Eingabe der neuen Lieferadresse
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPLIEFERADRESSE_NEUELIEFERADRESSE_PLAUSI', 12);

/**
 * Setzen der neuen Lieferadresse in die Bestellung
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPLIEFERADRESSE_NEUELIEFERADRESSE', 13);

/**
 * Setzen der vorhandenen Lieferadresse in die Bestellung
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPLIEFERADRESSE_VORHANDENELIEFERADRESSE', 14);

/**
 * Setzen der Lieferadresse aus Rechnungsadresse in die Bestellung
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPLIEFERADRESSE_RECHNUNGLIEFERADRESSE', 15);

/**
 * Plausibilitätsprüfung nach Wahl der Zahlungsart
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPZAHLUNG_PLAUSI', 16);

/**
 * Setzen des Guthabens im Step Bestätigung
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPBESTAETIGUNG_GUTHABENVERRECHNEN', 17);

/**
 * Plausibilitätsprüfung im Step Bestätigung ob Guthaben genutzt wurde
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE_STEPBESTAETIGUNG_GUTHABEN_PLAUSI', 18);

/**
 * Kurz vor der Anzeige im Bestellvorgang
 *
 * @file bestellvorgang.php
 */
define('HOOK_BESTELLVORGANG_PAGE', 19);

/**
 * Kurz vor der Anzeige eines Artikels in der Druckansicht
 *
 * @removed
 */
define('HOOK_DRUCKANSICHT_PAGE_ARTIKEL', 20);

/**
 * Kurz vor der Anzeige eines Textes in der Druckansicht
 *
 * @removed
 */
define('HOOK_DRUCKANSICHT_PAGE_TEXT', 21);

/**
 * Kurz vor der Anzeige in der Artikelübersicht
 *
 * @file filter.php
 */
define('HOOK_FILTER_PAGE', 22);

/**
 * Kurz vor der Anzeige in der JTL Seite
 *
 * @file jtl.php
 */
define('HOOK_JTL_PAGE', 23);

/**
 * Gekommen von einer Seite um sich einzuloggen und kurz vor dem Redirect zurück
 *
 * @file jtl.php
 */
define('HOOK_JTL_PAGE_REDIRECT', 24);

/**
 * Plausibilitätsprüfung nach Ändern von Kundendaten
 *
 * @file jtl.php
 */
define('HOOK_JTL_PAGE_KUNDENDATEN_PLAUSI', 25);

/**
 * Bei der Löschung eines Kundenkontos
 *
 * @file jtl.php
 */
define('HOOK_JTL_PAGE_KUNDENACCOUNTLOESCHEN', 26);

/**
 * Anzeige des Kundenkontos
 *
 * @file jtl.php
 * @param Lieferadresse[] deliveryAddresses - since 5.0.0
 */
define('HOOK_JTL_PAGE_MEINKKONTO', 27);

/**
 * Kommt von einer Seite um sich als Kunde einzuloggen (JTL)
 *
 * @file jtl.php
 */
define('HOOK_JTL_PAGE_REDIRECT_DATEN', 28);

/**
 * Kurz vor der Anzeige des Kontaktformulars
 *
 * @file kontakt.php
 */
define('HOOK_KONTAKT_PAGE', 29);

/**
 * Plausibilitätsprüfung nach Abschicken des Kontaktformulars
 *
 * @file kontakt.php
 */
define('HOOK_KONTAKT_PAGE_PLAUSI', 30);

/**
 * Kurz vor der Anzeige in der Artikelübersicht
 *
 * @file navi.php
 */
define('HOOK_NAVI_PAGE', 31);

/**
 * Kurz vor der Anzeige in der News Detailansicht
 *
 * @file news.php
 * @param \JTL\News\Item newsItem - since 5.0.0
 * @param \JTL\Pagination\Pagination pagination - since 5.0.0
 */
define('HOOK_NEWS_PAGE_DETAILANSICHT', 32);

/**
 * Kurz vor der Anzeige in der News Übersicht
 *
 * @file news.php
 * @param \JTL\News\Category category - since 5.0.0
 * @param Collection items - since 5.0.0
 */
define('HOOK_NEWS_PAGE_NEWSUEBERSICHT', 33);

/**
 * Plausibilitätsprüfung nach abschicken eines Newskommentars
 *
 * @file news.php
 */
define('HOOK_NEWS_PAGE_NEWSKOMMENTAR_PLAUSI', 34);

/**
 * Kurz bevor der Newskommentar in die Datenbank gelangt
 *
 * @file news.php
 * @param comment
 */
define('HOOK_NEWS_PAGE_NEWSKOMMENTAR_EINTRAGEN', 35);

/**
 * Kurz vor der Anzeige der Newsletter an- und abmeldung
 *
 * @file newsletter.php
 */
define('HOOK_NEWSLETTER_PAGE', 36);

/**
 * Kurz bevor ein Newsletterempfänger in die Datenbank eingetragen wird
 *
 * @file newsletter.php
 * @param oNewsletterEmpfaenger
 */
define('HOOK_NEWSLETTER_PAGE_EMPFAENGEREINTRAGEN', 37);

/**
 * Kurz bevor ein Newsletterempfänger gelöscht wird
 *
 * @file newsletter.php
 * @param oNewsletterEmpfaenger
 */
define('HOOK_NEWSLETTER_PAGE_EMPFAENGERLOESCHEN', 38);

/**
 * Kurz bevor ein Newsletterempfänger freigeschalten wird
 *
 * @file newsletter.php
 * @param oNewsletterEmpfaenger
 */
define('HOOK_NEWSLETTER_PAGE_EMPFAENGERFREISCHALTEN', 39);

/**
 * Kurz bevor die Registrierungsseite angezeigt wird
 *
 * @file registrieren.php
 */
define('HOOK_REGISTRIEREN_PAGE', 40);

/**
 * Plausibilitätsprüfung nach abschicken einer Kundenregistrierung
 *
 * @file registrieren_inc.php
 * @param nReturnValue
 * @param fehlendeAngaben
 */
define('HOOK_REGISTRIEREN_PAGE_REGISTRIEREN_PLAUSI', 41);

/**
 * Kurz bevor die Anzeige zur Seite ausgegeben wird
 *
 * @file seite.php
 */
define('HOOK_SEITE_PAGE', 42);

/**
 * Kurz vor der Rückgabe der Stadt zu einer PLZ (Ajax)
 *
 * @file toolsajax.server.php
 */
define('HOOK_TOOLSAJAXSERVER_PAGE_KUNDENFORMULARPLZ', 43);

/**
 * Kurz vor der Rückgabe der Suchvorschläge (Ajax)
 *
 * @file toolsajax.server.php
 * @param cValue
 * @param nkeyCode
 * @param cElemSearchID
 * @param cElemSuggestID
 * @param cElemSubmitID
 * @param objResponse
 */
define('HOOK_TOOLSAJAXSERVER_PAGE_SUCHVORSCHLAG', 44);

/**
 * Kurz vor der Rückgabe der Variationskombination (Ajax)
 *
 * @file toolsajax.server.php/io_inc.php
 * @param objResponse
 * @param oArtikel
 */
define('HOOK_TOOLSAJAXSERVER_PAGE_TAUSCHEVARIATIONKOMBI', 45);

/**
 * Kurz vor der Ausgabe der Artikeldetailseite (Ajax)
 *
 * @file toolsajax.server.php
 * @deprecated deprecated since version 4.00
 */
define('HOOK_TOOLSAJAXSERVER_PAGE_ARTIKELDETAIL', 46);

/**
 * @removed in 5.0.0
 */
define('HOOK_UMFRAGE_PAGE', 47);

/**
 * @removed in 5.0.0
 */
define('HOOK_UMFRAGE_PAGE_UEBERSICHT', 48);

/**
 * @removed in 5.0.0
 */
define('HOOK_UMFRAGE_PAGE_DURCHFUEHRUNG', 49);

/**
 * @removed in 5.0.0
 */
define('HOOK_UMFRAGE_PAGE_UMFRAGEERGEBNIS', 50);

/**
 * Kurz vor der Anzeige der Vergleichsliste
 *
 * @file vergleichsliste.php|toolsajax.server.php
 */
define('HOOK_VERGLEICHSLISTE_PAGE', 51);

/**
 * Kurz vor der Anzeige des Warenkorbs
 *
 * @file warenkorb.php
 */
define('HOOK_WARENKORB_PAGE', 52);

/**
 * Nach der Ermittlung der Versantkosten im Warenkorb
 *
 * @file warenkorb.php
 */
define('HOOK_WARENKORB_PAGE_ERMITTLEVERSANDKOSTEN', 53);

/**
 * Nach der Annahme eines Kupons im Warenkorb
 *
 * @file warenkorb.php
 */
define('HOOK_WARENKORB_PAGE_KUPONANNEHMEN', 54);

/**
 * Plausibilitätsprüfung für die Annahme eines Kupons im Warenkorb
 *
 * @file warenkorb.php
 * @param array error
 * @param int - nReturnValue
 */
define('HOOK_WARENKORB_PAGE_KUPONANNEHMEN_PLAUSI', 55);

/**
 * Vor dem Einfügen des Gratisgeschenkes
 *
 * @file warenkorb.php
 */
define('HOOK_WARENKORB_PAGE_GRATISGESCHENKEINFUEGEN', 56);

/**
 * Vor der Rückgabe der XSelling Artikel in den Artikeldetails
 *
 * @file artikel_inc.php
 * @param kArtikel
 * @param xSelling
 */
define('HOOK_ARTIKEL_INC_XSELLING', 57);

/**
 * Vor der Rückgabe des MetaTitle Artikel in den Artikeldetails
 *
 * @file Artikel.php
 * @param string cTitle
 * @since 4.0
 */
define('HOOK_ARTIKEL_INC_METATITLE', 58);

/**
 * Vor der Rückgabe der MetaDescription Artikel in den Artikeldetails
 *
 * @file Artikel.php
 * @param string  cDesc
 * @param Artikel oArtikel
 * @since 4.0
 */
define('HOOK_ARTIKEL_INC_METADESCRIPTION', 59);

/**
 * Vor der Rückgabe der MetaKeywords Artikel in den Artikeldetails
 *
 * @file Artikel.php
 * @param string keywords
 * @since 4.0
 */
define('HOOK_ARTIKEL_INC_METAKEYWORDS', 60);

/**
 * Plausibilitätsprüfung für die Versendung einer Frage zum Produkt
 *
 * @file artikel_inc.php
 */
define('HOOK_ARTIKEL_INC_FRAGEZUMPRODUKT_PLAUSI', 61);

/**
 * Vor der Sendung einer Frage zum Produkt
 *
 * @file artikel_inc.php
 */
define('HOOK_ARTIKEL_INC_FRAGEZUMPRODUKT', 62);

/**
 * Plausibilitätsprüfung für die Benachrichtigung Artikel in den Artikeldetails
 *
 * @file artikel_inc.php
 */
define('HOOK_ARTIKEL_INC_BENACHRICHTIGUNG_PLAUSI', 65);

/**
 * Vor der Sendung der Benachrichtigung in den Artikeldetails
 *
 * @file artikel_inc.php
 * @param Benachrichtigung - since 4.07
 */
define('HOOK_ARTIKEL_INC_BENACHRICHTIGUNG', 66);

/**
 * Im Switch der Artikelhinweise in den Artikeldetails
 *
 * @file artikel_inc.php
 */
define('HOOK_ARTIKEL_INC_ARTIKELHINWEISSWITCH', 67);

/**
 * @removed in 5.0.0
 */
define('HOOK_ARTIKEL_INC_PRODUKTTAGGING', 68);

/**
 * Im Switch der Bewertungshinweise in den Artikeldetails
 *
 * @file artikel_inc.php
 * @param error
 */
define('HOOK_ARTIKEL_INC_BEWERTUNGHINWEISSWITCH', 69);

/**
 * Nach der Ermittlung der zuletzt angesehenden Artikel in den Artikeldetails
 *
 * @file artikel_inc.php
 */
define('HOOK_ARTIKEL_INC_ZULETZTANGESEHEN', 70);

/**
 * Nach der Zusammenfassung einer Variationskombination und einem Vaterartikel in den Artikeldetails
 *
 * @file artikel_inc.php
 * @param article
 */
define('HOOK_ARTIKEL_INC_FASSEVARIVATERUNDKINDZUSAMMEN', 71);

/**
 * Kurz vor der Rückgabe der ähnlichen Artikel in den Artikeldetails
 *
 * @file Artikel.php|artikel_inc.php
 * @param array oArtikel_arr
 * @param int   kArtikel
 */
define('HOOK_ARTIKEL_INC_AEHNLICHEARTIKEL', 72);

/**
 * Kurz vor der Sendung der Email für eine Neukundenregistrierung während des Einfügens einer Bestellung
 *
 * @file bestellabschluss_inc.php
 */
define('HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB_NEUKUNDENREGISTRIERUNG', 73);

/**
 * Kurz vor dem Eintragen der Rechnungsadresse in die Datenbank während des Einfügens einer Bestellung
 *
 * @param \JTL\Checkout\Rechnungsadresse billingAddress - since 5.0.0
 * @file bestellabschluss_inc.php
 */
define('HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB_RECHNUNGSADRESSE', 74);

/**
 * before saving an order to the database
 *
 * @file bestellabschluss_inc.php
 * @param oBestellung - Bestellung
 */
define('HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB', 75);

/**
 * Plausibilitätsprüfung für die unregistrierte Registrierung im Bestellvorgang
 *
 * @file bestellvorgang_inc.php
 * @param nReturnValue
 * @param fehlendeAngaben
 * @param Customer
 * @param cPost_arr
 */
define('HOOK_BESTELLVORGANG_INC_UNREGISTRIERTBESTELLEN_PLAUSI', 76);

/**
 * at the end of pruefeUnregistriertBestellen() if successful
 *
 * @file bestellvorgang_inc.php
 */
define('HOOK_BESTELLVORGANG_INC_UNREGISTRIERTBESTELLEN', 77);

/**
 * before saving a rating to the database
 *
 * @file bewertung_inc.php
 * @param JTL\Review\ReviewModel rating
 */
define('HOOK_BEWERTUNG_INC_SPEICHERBEWERTUNG', 78);

/**
 * before saving Bewertunghilfreich to database
 *
 * @file bewertung_inc.php
 * @param rating
 */
define('HOOK_BEWERTUNG_INC_SPEICHERBEWERTUNGHILFREICH', 79);

/**
 * @file Boxen.php
 */
define('HOOK_BOXEN_INC_SCHNELLKAUF', 80);

/**
 * @file Boxen.php
 * @param \JTL\Boxes\Items\AbstractBox box
 */
define('HOOK_BOXEN_INC_ZULETZTANGESEHEN', 81);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                        cache_tags
 */
define('HOOK_BOXEN_INC_TOPANGEBOTE', 82);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                        cache_tags
 */
define('HOOK_BOXEN_INC_NEUIMSORTIMENT', 83);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                        cache_tags
 */
define('HOOK_BOXEN_INC_SONDERANGEBOTE', 84);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                        cache_tags
 */
define('HOOK_BOXEN_INC_BESTSELLER', 85);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                        cache_tags
 */
define('HOOK_BOXEN_INC_ERSCHEINENDEPRODUKTE', 86);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                        cache_tags
 */
define('HOOK_BOXEN_INC_SUCHWOLKE', 87);

/**
 * @removed in 5.0.0
 */
define('HOOK_BOXEN_INC_TAGWOLKE', 88);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 */
define('HOOK_BOXEN_INC_WUNSCHZETTEL', 89);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 */
define('HOOK_BOXEN_INC_VERGLEICHSLISTE', 90);

/**
 * @file tools.Global.php
 */
define('HOOK_BOXEN_INC_SUCHSPECIALURL', 91);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                     cache_tags
 */
define('HOOK_BOXEN_INC_TOPBEWERTET', 92);

/**
 * @file Boxen.php
 */
define('HOOK_BOXEN_INC_NEWS', 93);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                     cache_tags
 */
define('HOOK_BOXEN_INC_NEWSKATEGORIE', 94);

/**
 * @file Boxen.php
 * @param JTL\Boxes\Items\BoxInterface box
 * @param array                     cache_tags
 */
define('HOOK_BOXEN_INC_UMFRAGE', 95);

/**
 * after switching job type
 *
 * @file cron_inc.php
 * @param nLimitM
 */
define('HOOK_CRON_INC_SWITCH', 96);

/**
 * @removed
 */
define('HOOK_JOBQUEUE_INC_SWITCH', 97);

/**
 * at the end of gibRedirect() before saving redirect to session
 *
 * @file jtl_inc.php
 * @param cRedirect
 * @param oRedirect
 */
define('HOOK_JTL_INC_SWITCH_REDIRECT', 98);

/**
 * at the end of the file
 *
 * @file letzterinclude.php
 */
define('HOOK_LETZTERINCLUDE_INC', 99);

/**
 * after template switch in sendeMail()
 *
 * @file mailTools.php
 * @param \JTL\Smarty\JTLSmarty mailsmarty
 * @param \JTL\Mail\Renderer\RendererInterface renderer - since 5.0.0
 * @param object mail - null since 5.0.0
 * @param int kEmailvorlage
 * @param int kSprache
 * @param string cPluginBody - empty string since 5.0.0
 * @param Emailvorlage
 * @param \JTL\Mail\Template\TemplateInterface template - since 5.0.0
 * @param \JTL\Mail\Template\Model model - since 5.0.0
 */
define('HOOK_MAILTOOLS_INC_SWITCH', 100);

/**
 * after creating the navigation object, before assigning to smarty
 *
 * @file tools.Global.php
 * @param navigation
 */
define('HOOK_TOOLSGLOBAL_INC_SWITCH_CREATENAVIGATION', 101);

/**
 * @removed
 */
define('HOOK_TOOLSGLOBAL_INC_PREISSTRINGLOCALIZED', 102);

/**
 * at the end of gibMwStVersandString() before returning the string
 *
 * @file Artikel.php
 * @param string  - cVersandhinweis
 * @param Artikel - oArtikel
 * @since 4.0
 */
define('HOOK_TOOLSGLOBAL_INC_MWSTVERSANDSTRING', 103);

/**
 * at the beginning of baueURL()
 *
 * @file tools.Global.php
 * @param obj
 * @param art
 */
define('HOOK_TOOLSGLOBAL_INC_SWITCH_BAUEURL', 104);

/**
 * at the end of setzeLinks()
 *
 * @file tools.Global.php
 */
define('HOOK_TOOLSGLOBAL_INC_SETZELINKS', 105);

/**
 * after calculating shiping costs
 *
 * @file tools.Global.php
 * @param fPreis
 */
define('HOOK_TOOLSGLOBAL_INC_BERECHNEVERSANDPREIS', 106);

/**
 * @removed in 5.0.0
 */
define('HOOK_TOOLSGLOBAL_INC_SWITCH_PARSENEWSTEXT', 107);

/**
 * @file tools.Global.php
 */
define('HOOK_TOOLSGLOBAL_INC_SWITCH_SETZESPRACHEUNDWAEHRUNG_SPRACHE', 108);

/**
 * at the end of setzeSpracheUndWaehrungLink()
 *
 * @file tools.Global.php
 * @param oNaviFilter
 * @param oZusatzFilter
 * @param cSprachURL
 * @param oAktuellerArtikel
 * @param kSeite
 * @param kLink
 * @param AktuelleSeite
 */
define('HOOK_TOOLSGLOBAL_INC_SETZESPRACHEUNDWAEHRUNG_WAEHRUNG', 109);

/**
 * after loading an article
 *
 * @file Artikel.php
 * @param Artikel - oArtikel
 * @param array   - cacheTags - list of associated cache tags (since 4.0)
 * @param bool    - cached - true when fetched from object cache (since 4.0)
 */
define('HOOK_ARTIKEL_CLASS_FUELLEARTIKEL', 110);

/**
 * at the end of Attribut::loadFromDB()
 *
 * @file Attribut.php
 */
define('HOOK_ATTRIBUT_CLASS_LOADFROMDB', 111);

/**
 * at the end of Bestellung::fuelleBestellung()
 *
 * @file Bestellung.php
 * @param Bestellung - oBestellung (@since 4.05)
 */
define('HOOK_BESTELLUNG_CLASS_FUELLEBESTELLUNG', 112);

/**
 * at the end of holeHilfreichsteBewertung()
 *
 * @file Bewertung.php
 */
define('HOOK_BEWERTUNG_CLASS_HILFREICHSTEBEWERTUNG', 113);

/**
 * in holeProduktBewertungen()
 *
 * @file Bewertung.php
 */
define('HOOK_BEWERTUNG_CLASS_SWITCH_SORTIERUNG', 114);

/**
 * after loading a rating
 *
 * @file Bewertung.php
 * @param Bewertung - oBewertung
 */
define('HOOK_BEWERTUNG_CLASS_BEWERTUNG', 115);

/**
 * @file Eigenschaft.php
 */
define('HOOK_EIGENSCHAFT_CLASS_LOADFROMDB', 116);

/**
 * @file EigenschaftsWert.php
 */
define('HOOK_EIGENSCHAFTWERT_CLASS_LOADFROMDB', 117);

/**
 * after loading a company from the database
 *
 * @file Firma.php
 * @param Firma instance - since 5.0.0
 */
define('HOOK_FIRMA_CLASS_LOADFROMDB', 118);

/**
 * after loading a manufacturer from the database
 *
 * @file Hersteller.php
 * @param Hersteller - oHersteller
 * @param array      - cacheTags - list of associated cache tags (since 4.0)
 * @param bool       - cached - true if fetched from object cache (since 4.0)
 */
define('HOOK_HERSTELLER_CLASS_LOADFROMDB', 119);

/**
 * @file Kategorie.php
 * @param Kategorie - oKategorie
 * @param array     - cacheTags - list of associated cache tags  (since 4.0)
 * @param bool      - cached - true if fetched from object cache  (since 4.0)
 */
define('HOOK_KATEGORIE_CLASS_LOADFROMDB', 120);

/**
 * @file Kunde.php
 */
define('HOOK_KUNDE_CLASS_LOADFROMDB', 121);

/**
 * @file Lieferadresse.php
 */
define('HOOK_LIEFERADRESSE_CLASS_LOADFROMDB', 122);

/**
 * @file Merkmal.php
 * @param Merkmal instance - since 5.0.0
 */
define('HOOK_MERKMAL_CLASS_LOADFROMDB', 123);

/**
 * @file MerkmalWert.php
 * @param MerkmalWert - oMerkmalWert
 */
define('HOOK_MERKMALWERT_CLASS_LOADFROMDB', 124);

/**
 * after loading a delivery address from the database
 * @file Rechnungsadresse.php
 */
define('HOOK_RECHNUNGSADRESSE_CLASS_LOADFROMDB', 125);

/**
 * after adding an article to the cart
 *
 * @file Warenkorb.php
 * @param int   - kArtikel
 * @param array - oPosition_arr
 * @param float - nAnzahl
 * @param bool  - exists
 */
define('HOOK_WARENKORB_CLASS_FUEGEEIN', 126);

/**
 * after adding an article to the wishlist
 *
 * @file Wunschliste.php
 */
define('HOOK_WUNSCHLISTE_CLASS_FUEGEEIN', 127);

/**
 * after adding an article to the compare list
 *
 * @file Vergleichsliste.php
 */
define('HOOK_VERGLEICHSLISTE_CLASS_EINFUEGEN', 128);

/**
 * after checking current link type
 *
 * @file seite.php
 */
define('HOOK_SEITE_PAGE_IF_LINKART', 129);

/**
 * at the end of setzeSmartyWeiterleitung()
 *
 * @file bestellabschluss_inc.php
 */
define('HOOK_BESTELLABSCHLUSS_INC_SMARTYWEITERLEITUNG', 130);

/**
 * after global includes (when not using JTL_INCLUDE_ONLY_DB)
 *
 * @file globalinclude.php
 */
define('HOOK_GLOBALINCLUDE_INC', 131);

/**
 * at the very beginning to catch POST/GET params
 *
 * @file index.php|navi.php
 */
define('HOOK_INDEX_NAVI_HEAD_POSTGET', 132);

/**
 * after instanciating JTLSmarty
 *
 * @file smartyInclude.php
 * @param \JTL\Smarty\JTLSmarty $smarty
 */
define('HOOK_SMARTY_INC', 133);

/**
 * at the beginning of holeJobs()
 *
 * @file lastjobs.php
 * @param array jobs - since 5.0.0
 */
define('HOOK_LASTJOBS_HOLEJOBS', 134);

/**
 * @file class.core.NiceDB.php
 * @param mysqlerrno
 * @param statement
 * @param time
 */
define('HOOK_NICEDB_CLASS_EXECUTEQUERY', 135);

/**
 * after sending an email
 *
 * @file mailTools.php
 */
define('HOOK_MAILTOOLS_VERSCHICKEMAIL_GESENDET', 136);

/**
 * before writing the fetched output
 *
 * @file do_export.php
 */
define('HOOK_DO_EXPORT_OUTPUT_FETCHED', 137);

/**
 * at the beginning of bearbeite() in dbeS
 *
 * @file Bilder_xml.php
 * @param Pfad
 * @param Artikel
 * @param Kategorie
 * @param Eigenschaftswert
 * @param Hersteller
 * @param Merkmalwert
 * @param Merkmal
 * @param Konfiggruppe
 */
define('HOOK_BILDER_XML_BEARBEITE', 138);

/**
 * before writing fetched output
 *
 * @file cron_exportformate.php
 */
define('HOOK_CRON_EXPORTFORMATE_OUTPUT_FETCHED', 139);

/**
 * at the end of smarty outputfilter
 *
 * @file JTLSmarty.php
 * @param \JTL\Smarty\JTLSmarty smarty
 * @param \JTL\phpQuery\phpQueryObject document
 */
define('HOOK_SMARTY_OUTPUTFILTER', 140);

/**
 * after deleting of all special positions in the cart
 *
 * @file warenkorb_inc.php
 */
define('HOOK_WARENKORB_LOESCHE_ALLE_SPEZIAL_POS', 141);

/**
 * at the beginning of Shop::seoCheck()
 *
 * @file class.core.Shop.php
 */
define('HOOK_SEOCHECK_ANFANG', 142);

/**
 * at the end of Shop::seoCheck()
 *
 * @file class.core.Shop.php
 */
define('HOOK_SEOCHECK_ENDE', 143);

/**
 * after defining $cSh and $cPh
 *
 * @file notify.php
 */
define('HOOK_NOTIFY_HASHPARAMETER_DEFINITION', 144);

/**
 * in holLoginKunde() after loading a customer, before decryption
 *
 * @file Kunde.php
 * @param oKunde
 * @param oUser
 * @param cBenutzername
 * @param oKucPasswortnde
 */
define('HOOK_KUNDE_CLASS_HOLLOGINKUNDE', 145);

/**
 * when no link if found for seo string
 *
 * @file index.php|navi.php
 * @param seo
 */
define('HOOK_INDEX_SEO_404', 146);

/**
 * triggered when checkbox has plugin special functions and is checked by a customer
 *
 * @file CheckBox.php
 * @param \JTL\CheckBox - oCheckBox
 */
define('HOOK_CHECKBOX_CLASS_TRIGGERSPECIALFUNCTION', 147);

/**
 * at the beginning of gibNaviMetaDescription()
 *
 * @file filter_inc.php
 */
define('HOOK_FILTER_INC_GIBNAVIMETADESCRIPTION', 148);

/**
 * at the beginning of gibNaviMetaKeywords()
 *
 * @file filter_inc.php
 */
define('HOOK_FILTER_INC_GIBNAVIMETAKEYWORDS', 149);

/**
 * at the beginning of gibNaviMetaTitle()
 *
 * @file filter_inc.php
 */
define('HOOK_FILTER_INC_GIBNAVIMETATITLE', 150);

/**
 * in bearbeiteInsert() after inserting an article into the database
 *
 * @file Artikel_xml.php
 * @param oArtikel
 */
define('HOOK_ARTIKEL_XML_BEARBEITEINSERT', 151);

/**
 * in bearbeiteDeletes() after deleting an article from the database
 *
 * @file Artikel_xml.php
 * @param kArtikel - article ID
 */
define('HOOK_ARTIKEL_XML_BEARBEITEDELETES', 152);

/**
 * in sendeMail() before actually sending an email
 *
 * @file mailTools.php
 * @param \JTL\Smarty\JTLSmarty mailsmarty
 * @param mail - MailInterface since 5.0.0
 * @param int kEmailvorlage - 0 since 5.0.0
 * @param int kSprache
 * @param string cPluginBody - empty string since 5.0.0
 * @param object Emailvorlage - null since 5.0.0
 * @param \JTL\Mail\Template\TemplateInterface template - since 5.0.0
 */
define('HOOK_MAILTOOLS_SENDEMAIL_ENDE', 153);

/**
 * at the end of mappeBestellvorgangZahlungshinweis() when creating payment method notice
 *
 * @file bestellvorgang_inc.php
 * @param cHinweis
 * @param nHinweisCode
 */
define('HOOK_BESTELLVORGANG_INC_MAPPEBESTELLVORGANGZAHLUNGSHINWEIS', 154);

/**
 * to create new functions for xajax in the admin area
 *
 * @file admin/toolsajax.server.php
 * @param xajax
 */
define('HOOK_TOOLSAJAX_SERVER_ADMIN', 155);

/**
 * after the creating of a new session - $_SESSION is available
 *
 * @file class.core.Session.php
 */
define('HOOK_CORE_SESSION_CONSTRUCTOR', 156);

/**
 * at the end of gibBelieferbareLaender()
 *
 * @file tools.Global.php
 * @param oLaender_arr - array of countries
 */
define('HOOK_TOOLSGLOBAL_INC_GIBBELIEFERBARELAENDER', 157);

/**
 * after executing job
 *
 * @file jobqueue_inc.php
 * @param JTL\Cron\QueueEntry oJobQueue
 * @param JTL\Cron\Job job
 * @param Psr\Log\LoggerInterface logger
 */
define('HOOK_JOBQUEUE_INC_BEHIND_SWITCH', 158);

/**
 * after filling an order
 *
 * @file Bestellungen_xml.php
 * @param oBestellung - order object
 * @param oKunde - customer object
 * @param oBestellungWawi
 */
define('HOOK_BESTELLUNGEN_XML_BEARBEITESET', 159);

/**
 * before intercepting search
 *
 * @file navi.php
 * @param cValue - search string
 * @param bExtendedJTLSearch
 */
define('HOOK_NAVI_PRESUCHE', 160);

/**
 * before building article count
 *
 * @file navi.php
 * @param bExtendedJTLSearch
 * @param oExtendedJTLSearchResponse
 * @param cValue
 * @param nArtikelProSeite
 * @param nSeite
 * @param nSortierung
 * @param bLagerbeachten
 */
define('HOOK_NAVI_SUCHE', 161);

/**
 * at the beginning of gibArtikelabhaengigeVersandkosten()
 *
 * @file class.helper.Versandart.php (since 4.0, tools.Global.php before)
 * @param oArtikel
 * @param cLand
 * @param nAnzahl
 * @param bHookReturn
 */
define('HOOK_TOOLS_GLOBAL_GIBARTIKELABHAENGIGEVERSANDKOSTEN', 162);

/**
 * at the beginning of pruefeArtikelabhaengigeVersandkosten()
 *
 * @file class.helper.Versandart.php (since 4.0, tools.Global.php before)
 * @param oArtikel
 * @param bHookReturn
 */
define('HOOK_TOOLS_GLOBAL_PRUEFEARTIKELABHAENGIGEVERSANDKOSTEN', 163);

/**
 * after urlNotFoundRedirect() and setting 404 header
 *
 * @file tools.Global.php
 * @param isFileNotFound
 * @param <string>
 */
define('HOOK_PAGE_NOT_FOUND_PRE_INCLUDE', 164);

/**
 * after the creating of the sitemap
 *
 * @file sitemapexport.php
 * @param JTL\Sitemap\Export instance
 * @param nAnzahlURL_arr
 * @param fTotalZeit
 */
define('HOOK_SITEMAP_EXPORT_GENERATED', 165);

/**
 * before putting an article into the cart at the beginning of checkeWarenkorbEingang()
 *
 * @file tools.Global.php
 * @param kArtikel - the article ID
 * @param fAnzahl - the amount of this article
 */
define('HOOK_TOOLS_GLOBAL_CHECKEWARENKORBEINGANG_ANFANG', 166);

/**
 * after putting an article onto the wishlist in checkeWarenkorbEingang()
 *
 * @file tools.Global.php
 * @param kArtikel - the article ID
 * @param fAnzahl - the amount of this article
 * @param AktuellerArtikel - the current article
 */
define('HOOK_TOOLS_GLOBAL_CHECKEWARENKORBEINGANG_WUNSCHLISTE', 167);

/**
 * after calling NiceDB::insertRow()
 *
 * @file class.core.NiceDB.php
 * @param mysqlerrno - error code
 * @param statement - the executed sql statement
 */
define('HOOK_NICEDB_CLASS_INSERTROW', 168);

/**
 * at the end of filter.php before displaying article list
 *
 * @file filter.php
 */
define('HOOK_FILTER_ENDE', 169);

/**
 * at the end of navi.php before displaying article list
 *
 * @file navi.php
 */
define('HOOK_NAVI_ENDE', 170);

/**
 * in bearbeiteHerstellerDeletes() after deleting manufacturers from the database
 *
 * @file Hersteller_xml.php
 * @param kHersteller - manufacturer ID
 */
define('HOOK_HERSTELLER_XML_BEARBEITEDELETES', 171);

/**
 * in bearbeiteDeletes() after deleting categories from the database
 *
 * @file Kategorie_xml.php
 * @param kKategorie - category ID
 */
define('HOOK_KATEGORIE_XML_BEARBEITEDELETES', 172);

/**
 * in bearbeiteInsert() when inserting manufacturers into the database
 *
 * @file Hersteller_xml.php
 * @param oHersteller - manufacturer object
 */
define('HOOK_HERSTELLER_XML_BEARBEITEINSERT', 173);

/**
 * in bearbeiteInsert() when inserting categories into the database
 *
 * @file Kategorien_xml.php
 * @param oKategorie - category object
 */
define('HOOK_KATEGORIE_XML_BEARBEITEINSERT', 174);

/**
 * before assigning css/js resources to smarty
 *
 * @file letzterInclude.php
 * @param cCSS_arr - template css
 * @param cJS_arr - template js
 * @param cPluginCss_arr - plugin css
 * @param cPluginCssConditional_arr - plugin css with condition
 * @param cPluginJsHead_arr - plugin js for head
 * @param cPluginJsBody_arr - plugin js for body
 */
define('HOOK_LETZTERINCLUDE_CSS_JS', 175);

/**
 * before inserting newsletter recipients into the database
 *
 * @file newsletter.php
 * @file newsletter_inc.php
 * @param oNewsletterEmpfaengerHistory *
 */
define('HOOK_NEWSLETTER_PAGE_HISTORYEMPFAENGEREINTRAGEN', 176);

/**
 * at the end of baueArtikelAnzahl()
 *
 * @file filter_inc.php
 * @param oAnzahl
 * @param FilterSQL
 * @param oSuchergebnisse
 * @param nArtikelProSeite
 * @param nLimitN
 */
define('HOOK_FILTER_INC_BAUEARTIKELANZAHL', 177);

/**
 * at the end of gibArtikelKeys()
 *
 * @file filter_inc.php
 * @param oArtikelKey_arr
 * @param FilterSQL
 * @param NaviFilter
 * @param SortierungsSQL
 */
define('HOOK_FILTER_INC_GIBARTIKELKEYS', 178);

/**
 * after getting images array in dbeS
 * @param Kategorie
 * @param Eigenschaftswert
 * @param Hersteller
 * @param Merkmalwert
 * @param Merkmal
 * @param Konfiggruppe
 */
define('HOOK_BILDER_XML_BEARBEITE_ENDE', 179);

/**
 * after getting all check boxes
 *
 * @file CheckBox.php
 * @param nAnzeigeOrt
 * @param kKundengruppe
 * @param bAktiv
 * @param bSprache
 * @param bSpecial
 * @param bLogging
 */
define('HOOK_CHECKBOX_CLASS_GETCHECKBOXFRONTEND', 180);

/**
 * before updating the order status
 *
 * @file Bestellungen_xml.php
 * @param status
 * @param oBestellung
 * @param oBestellungShop
 */
define('HOOK_BESTELLUNGEN_XML_BESTELLSTATUS', 181);

/**
 * at the end of the smarty output filter when mobile template is active
 *
 * @since 3.20
 */
define('HOOK_SMARTY_OUTPUTFILTER_MOBILE', 182);

/**
 * inside gibArtikelKeys() just before the SQL is being executed
 *
 * @file filter_inc.php
 * @since 4.0.5
 * @param cSQL
 * @param FilterSQL
 * @param NaviFilter
 * @param SortierungsSQL
 * @param cLimitSQL
 */
define('HOOK_FILTER_INC_GIBARTIKELKEYS_SQL', 183);

/**
 * at the end of bauFilterSQL() just before returning the build FilterSQL object
 *
 * @file filter_inc.php
 * @since 4.0.5
 * @param FilterSQL
 * @param NaviFilter
 */
define('HOOK_FILTER_INC_BAUFILTERSQL', 184);

/**
 * after flushing cache ID/tag
 *
 * @since 4.0
 * @file JTLCache.php
 */
define('HOOK_CACHE_FLUSH_AFTER', 200);

/**
 * after flushing cache ID/tag
 *
 * @since 4.0
 * @file JTLSmarty.php
 * @param smarty - Smarty\JTLSmarty
 */
define('HOOK_SMARTY_OUTPUTFILTER_CACHE', 202);

/**
 * after generation of a cache ID via JTLSmarty::getCacheID()
 *
 * @since 4.0
 * @file JTLSmarty.php
 * @param resource - the template name
 * @param conditions - the conditions used to generate the ID
 * @param cache_id - the generated ID
 */
define('HOOK_SMARTY_GENERATE_CACHE_ID', 203);

/**
 * List of all js/css groups to minify after generation via Template::getMinifyArray()
 *
 * @since 4.0
 * @file Template.php
 * @param groups - list of tpl groups
 * @param cache_tags - list of associated cache tags
 */
define('HOOK_CSS_JS_LIST', 204);

/**
 * before deleting a position from the cart
 *
 * @since 4.0
 * @file warenkob_inc.php
 * @param nPos - the position's index
 * @param position - the position itself
 */
define('HOOK_WARENKORB_LOESCHE_POSITION', 205);

/**
 * before deleting images in dbeS
 *
 * @since 4.0
 * @file Bilder_xml.php
 * @param array - Artikel - kArtikel
 * @param array - Kategorie - kKategorie
 * @param array - Eigenschaftswert - kEigeschaftswert
 * @param array - Hersteller - kHersteller
 * @param array - Merkmalwert - kMerkmal
 * @param array - Merkmal - kMerkmalwert
 */
define('HOOK_BILDER_XML_BEARBEITEDELETES', 206);

/**
 * after inserting order into the database
 *
 * @since 4.0
 * @file bestellabschluss_inc.php
 * @param stdClass - oBestellung - order object
 * @param stdClass - bestellID - bestellid object
 * @param stdClass - bestellstatus - order status
 */
define('HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB_ENDE', 207);

/**
 * @since 4.0
 * @file JTLSmarty.php
 * @param string - original
 * @param string - custom
 * @param string - fallback
 * @param string - out
 */
define('HOOK_SMARTY_FETCH_TEMPLATE', 208);

/**
 * @since 4.0
 * @file Bestellungen_xml.php
 * @param oBestellung
 * @param oBestellungAlt
 * @param oKunde
 */
define('HOOK_BESTELLUNGEN_XML_BEARBEITEUPDATE', 209);

/**
 * after canceling an order
 *
 * @since 4.0
 * @file Bestellungen_xml.php
 * @param oBestellung
 * @param oKunde
 * @param oModule
 */
define('HOOK_BESTELLUNGEN_XML_BEARBEITESTORNO', 210);

/**
 * after the creating of link groups
 *
 * @since 4.0
 * @file class.helper.Link.php
 * @param linkGroups - the link groups
 * @param cached - true if fetched from object cache
 * @param forced - true if re-creating was forced via param
 */
define('HOOK_BUILD_LINK_GROUPS', 211);

/**
 * after getting page content via getPageLinkLanguage()
 *
 * @since 4.0
 * @file class.helper.Link.php
 * @param cacheTags - array of associated cache tags
 * @param oLinkSprache
 * @param cached - true if fetched from object cache
 */
define('HOOK_GET_PAGE_LINK_LANGUAGE', 212);

/**
 * before handling the request
 *
 * @since 4.0
 * @file io.php
 * @param io
 * @param request
 */
define('HOOK_IO_HANDLE_REQUEST', 213);

/**
 * after setting the current page type
 *
 * @since 4.0
 * @file class.core.Shop.php
 * @param int    pageType
 * @param string pageName
 */
define('HOOK_SHOP_SET_PAGE_TYPE', 214);

/**
 * immediately before storing kunde in DB
 *
 * @param Customer - oKunde
 *@since 4.03
 * @file Kunde.php
 */
define('HOOK_KUNDE_DB_INSERT', 215);

/**
 * @since 4.03
 * @file Image.php
 * @param \Intervention\Image\Image - image
 * @param array  - settings
 * @param string - thumbnail
 */
define('HOOK_IMAGE_RENDER', 216);

/**
 * @since 4.03
 * @file navi.php
 * @param mixed - naviFilter
 * @param mixed - filterSQL
 */
define('HOOK_NAVI_CREATE', 217);

/**
 * @since 4.03
 * @file tools.Global.php
 * @param int    - min
 * @param int    - max
 * @param string - text
 */
define('HOOK_GET_DELIVERY_TIME_ESTIMATION_TEXT', 218);

/**
 * @since 4.03
 * @file class.helper.Kategorie.php
 * @param array - categories
 */
define('HOOK_GET_ALL_CATEGORIES', 219);

/**
 * @since 4.04
 * @file seite_inc.php
 * @param Collection - oNews_arr
 * @param array - cacheTags
 * @param bool  - cached
 */
define('HOOK_GET_NEWS', 220);

/**
 * @since 4.04
 * @file tools.Global.php
 * @param string - filterSQL
 * @param int    - conf
 */
define('HOOK_STOCK_FILTER', 221);

/**
 * @since 4.05
 * @file admin/includes/benutzerverwaltung_inc.php
 * @param Account    - oAccount
 * @param string     - type - VALIDATE|SAVE|LOCK|UNLOCK|DELETE
 * @param array      - &attribs - extended attributes (only used if type == VALIDATE or SAVE)
 * @param array      - &messages
 * @param bool|array - &result - true if success otherwise errormap
 */
define('HOOK_BACKEND_ACCOUNT_EDIT', 222);

/**
 * @since 4.05
 * @file admin/includes/benutzerverwaltung_inc.php
 * @param Account   - oAccount
 * @param \JTL\Smarty\JTLSmarty - smarty
 * @param array     - attribs - extended attributes
 * @param string    - &content
 */
define('HOOK_BACKEND_ACCOUNT_PREPARE_EDIT', 223);

/**
 * @since 4.05
 * @file seite_inc.php
 * @param array boxes - list of boxes for the home page
 */
define('HOOK_BOXEN_HOME', 224);

/**
 * in bearbeiteInsert() after inserting an article into the database
 *
 * @file QuickSync_xml.php
 * @param oArtikel
 */
define('HOOK_QUICKSYNC_XML_BEARBEITEINSERT', 225);

/**
 * after getting list of all manufacturers
 *
 * @since 4.05
 * @file class.helper.Hersteller.php
 * @param bool  - cached
 * @param array - cacheTags
 * @param array - manufacturers
 */
define('HOOK_GET_MANUFACTURERS', 226);

/**
 * @since 4.06
 * @file admin/templates/bootstrap/php/functions.php
 * @param \JTL\Backend\AdminAccount - oAdminAccount
 * @param string       - url
 */
define('HOOK_BACKEND_FUNCTIONS_GRAVATAR', 227);

/**
 * @param Cart - oWarenkorb
 * @param Bestellung - oBestellung
 * @since 4.06
 * @file includes/bestellabschluss_inc.php
 */
define('HOOK_BESTELLABSCHLUSS_INC_WARENKORBINDB', 228);

/**
 * after truncating tables in database
 *
 * @since 4.06
 * @file admin/shopzuruecksetzen.php
 */
define('HOOK_BACKEND_SHOP_RESET_AFTER', 229);

/**
 * on removing a cart position that has been deactivated / deleted in the meantime
 *
 * @param CartItem oPosition
 * @param bool     delete
 * @since 5.0.0
 * @file classes/Warenkorb.php
 */
define('HOOK_WARENKORB_CLASS_LOESCHEDEAKTIVIERTEPOS', 230);

/**
 * before the ordernumber is returned from baueBestellnummer().
 *
 * @since 4.06.14
 * @file includes/bestellabschluss_inc.php
 * @param int orderNo
 * @param string prefix
 * @param string suffix
 */
define('HOOK_BESTELLABSCHLUSS_INC_BAUEBESTELLNUMMER', 231);

/**
 * in ProductFilter::initBaseStates() after initializing the base filters
 *
 * @since 5.0.0
 * @file includes/src/Filter/ProductFilter.php
 * @param \JTL\Filter\ProductFilter productFilter
 */
define('HOOK_PRODUCTFILTER_INIT', 250);

/**
 * in ProductFilter::initStates() after initializing the active filters
 *
 * @since 5.0.0
 * @file includes/src/Filter/ProductFilter.php
 * @param \JTL\Filter\ProductFilter productFilter
 * @param array params
 */
define('HOOK_PRODUCTFILTER_INIT_STATES', 251);

/**
 * in ProductFilter::construct() when creating the instance
 *
 * @since 5.0.0
 * @file includes/src/Filter/ProductFilter.php
 * @param \JTL\Filter\ProductFilter productFilter
 */
define('HOOK_PRODUCTFILTER_CREATE', 252);

/**
 * in ProductFilter::construct() when creating the instance
 *
 * @since 5.0.0
 * @file Filter/ProductFilter.php
 * @param array select
 * @param array joins
 * @param array conditions
 * @param array groupBy
 * @param array having
 * @param array order
 * @param array limit
 * @param \JTL\Filter\ProductFilter productFilter
 */
define('HOOK_PRODUCTFILTER_GET_BASE_QUERY', 253);

/**
 * @since 5.0.0
 * @param JTL\Filter\SortingOptions\Factory $factory
 * @param \JTL\Filter\ProductFilter         $productFilter
 */
define('HOOK_PRODUCTFILTER_REGISTER_SEARCH_OPTION', 254);

/**
 * in Preise::__construct()
 *
 * @since 5.0.0
 * @file Preise.php
 * @param int customerGroupID
 * @param int customerID
 * @param int productID
 * @param int taxClassID
 * @param Preise prices
 */
define('HOOK_PRICES_CONSTRUCT', 260);

/**
 * in WarenkorbHelper::addToCartCheck()
 *
 * @since 5.0.0
 * @file WarenkorbHelper.php
 * @param Artikel product
 * @param int     quantity
 * @param array   attributes
 * @param int     accuracy
 * @param array   redirectParam
 */
define('HOOK_ADD_TO_CART_CHECK', 261);

/**
 * in WarenkorbHelper::setzePositionsPreise()
 *
 * @since 5.0.0
 * @file Warenkorb.php
 * @param mixed position
 * @param mixed oldPosition
 */
define('HOOK_SETZTE_POSITIONSPREISE', 262);

/**
 * in CaptchaService::isConfigured
 *
 * @since 5.0.0
 * @file src/Services/CaptchaService.php
 * @param bool isConfigured
 */
define('HOOK_CAPTCHA_CONFIGURED', 270);

/**
 * in CaptchaService::getHeadMarkup, CaptchaService::getBodyMarkup
 *
 * @since 5.0.0
 * @file src/Services/CaptchaService.php
 * @param bool   getBody
 * @param string markup
 */
define('HOOK_CAPTCHA_MARKUP', 271);

/**
 * in CaptchaService::validate
 *
 * @since 5.0.0
 * @file src/Services/CaptchaService.php
 * @param array requestData
 * @param bool  isValid
 */
define('HOOK_CAPTCHA_VALIDATE', 272);

/**
 * @since 5.0.0
 * @file admin/plugin.php
 * @param Plugin plugin
 * @param bool   hasError
 * @param string msg
 * @param string error
 * @param array  options
 */
define('HOOK_PLUGIN_SAVE_OPTIONS', 280);

/**
 * @since 5.0.0
 * @file includes/src/Sitemap/Export.php
 * @param \JTL\Sitemap\Factories\FactoryInterface[] factories
 * @param \JTL\Sitemap\Export instance
 */
define('HOOK_SITEMAP_EXPORT_GENERATE', 285);

/**
 * @since 5.0.0
 * @file includes/src/Sitemap/Export.php
 * @param \JTL\Sitemap\Export instance
 */
define('HOOK_SITEMAP_EXPORT_INIT', 286);

/**
 * @since 5.0.0
 * @file includes/src/Mail/Mailer.php
 * @param \JTL\Mail\Mailer mailer
 * @param \JTL\Mail\Mail\MailInterface mail
 */
define('HOOK_MAIL_PRERENDER', 290);

/**
 * @since 5.1.2
 * @file includes/src/Mail/Mailer.php
 * @param \JTL\Mail\Mailer mailer
 * @param \JTL\Mail\Mail\MailInterface mail
 * @param \PHPMailer\PHPMailer\PHPMailer phpmailer
 */
define('HOOK_MAILER_PRE_SEND', 291);

/**
 * @since 5.1.2
 * @file includes/src/Mail/Mailer.php
 * @param \JTL\Mail\Mailer mailer
 * @param \JTL\Mail\Mail\MailInterface mail
 * @param \PHPMailer\PHPMailer\PHPMailer phpmailer
 * @param bool status
 */
define('HOOK_MAILER_POST_SEND', 292);

/**
 * @since 5.0.0
 * @file includes/src/Link/Link.php
 * @param array data
 */
define('HOOK_LINK_PRE_MAP', 300);

/**
 * @since 5.0.0
 * @file includes/src/Link/Link.php
 * @param \JTL\Link\Link link
 */
define('HOOK_LINK_MAPPED', 301);

/**
 * @since 5.0.0
 * @file includes/src/Link/LinkGroup.php
 * @param \JTL\Link\LinkGroup group
 */
define('HOOK_LINKGROUP_MAPPED', 302);

/**
 * @since 5.0.0
 * @file includes/src/Link/LinkGroupList.php
 * @param \JTL\Link\LinkGroupList list
 */
define('HOOK_LINKGROUPS_LOADED', 303);

/**
 * Kurz vor dem Einfügen einer neuen/bisher unbekannten Lieferadresse in die DB
 *
 * @since 5.0.0
 * @file bestellabschluss_inc.php
 * @param JTL\Checkout\Lieferadresse deliveryAddress
 */
define('HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB_LIEFERADRESSE_NEU', 304);

/**
 * Zuordnung einer bekannten Lieferadresse zu der Bestellung, beim Einfügen einer Bestellung in die DB.
 *
 * @since 5.0.0
 * @file bestellabschluss_inc.php
 * @param int deliveryAddressID - Key der Lieferadresse als Integer
 */
define('HOOK_BESTELLABSCHLUSS_INC_BESTELLUNGINDB_LIEFERADRESSE_ALT', 305);

/**
 * @since 5.0.0
 * @file includes/src/Link/LinkGroupList.php
 * @param \JTL\Link\LinkGroupList list
 */
define('HOOK_LINKGROUPS_LOADED_PRE_CACHE', 306);

/**
 * @since 5.0.0
 * @file includes/src/Helpers/ShippingMethod.php
 * @param float price
 * @param Versandart|object shippingMethod
 * @param string iso
 * @param Artikel|stdClass additionalProduct
 * @param Artikel|null product
 */
define('HOOK_CALCULATESHIPPINGFEES', 307);

/**
 * @since 5.0.0
 * @file includes/src/Cart/Cart.php
 * @param int productID
 * @param CartItem[] positionItems
 * @param float qty
 */
define('HOOK_WARENKORB_ERSTELLE_SPEZIAL_POS', 310);

/**
 * @since 5.0.0
 * @file admin/io.php
 * @param \JTL\Backend\AdminIO io
 * @param string request
 */
define('HOOK_IO_HANDLE_REQUEST_ADMIN', 311);

/**
 * @since 5.0.0
 * @file Manager.php
 * @param Collection items - collection of JTL\Consent\ConsentModel\ConsentModel
 */
define('CONSENT_MANAGER_GET_ACTIVE_ITEMS', 320);

/**
 * @since 5.1.0
 * @file Preise.php
 * @param float|string netPrice
 * @param float|string defaultTax
 * @param float|string conversionTax
 * @param float|string newNetPrice
 */
define('HOOK_RECALCULATED_NET_PRICE', 321);

/**
 * @since 5.1.0
 * @file Preise.php
 * @param float|string $price
 * @param mixed        $currency
 * @param bool         $html
 * @param int          $decimals
 * @param string       $currencyName
 * @param string       $localized
 */
define('HOOK_LOCALIZED_PRICE_STRING', 330);

/**
 * @since 5.1.0
 * @file Cart.php
 * @param array $sum
 */
define('HOOK_CART_GET_LOCALIZED_SUM', 331);

/**
 * @since 5.1.0
 * @file includes/src/Helpers/Order.php
 * @param float &$creditToUse
 * @param float $cartTotal
 * @param float $customerCredit
 */
define('HOOK_BESTELLUNG_SETZEGUTHABEN', 335);
