<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Legenden
 */
$GLOBALS['TL_LANG']['tl_settings']['counter_legend'] = 'Zähler';
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_legend'] = 'Zähler: Statistik per E-Mail';

/**
 * Felder
 */
$GLOBALS['TL_LANG']['tl_settings']['counter_topx_pages'] = ['Anzahl Seiten', 'Wie viele Seiten die Bestenliste der Statistik zeigt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_topx_articles'] = ['Anzahl Artikel', 'Wie viele Artikel die Bestenliste der Statistik zeigt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_topx_news'] = ['Anzahl Nachrichten', 'Wie viele Nachrichten die Bestenliste der Statistik zeigt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_donotlog404'] = ['Fehler 404 nicht protokollieren', 'Aufrufe nicht vorhandener Seiten nicht im Systemprotokoll vermerken. Sinnvoll, wenn Suchmaschinen das Protokoll zumüllen.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_donotlogid'] = ['Fehlende Quell-ID nicht protokollieren', 'Nicht vermerken, wenn der Zähler einen Inhalt nicht zuordnen konnte.'];

$GLOBALS['TL_LANG']['tl_settings']['counter_mail'] = ['Statistik per E-Mail verschicken', 'Ein täglicher Cronjob verschickt die Bestenliste des Vortags, montags zusätzlich die der vergangenen Woche und am Monatsersten die des Vormonats. Je Inhaltsart geht eine eigene E-Mail an einen eigenen Verteiler.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_anzahl'] = ['Listenplätze', 'Wie viele Einträge die Bestenliste in der E-Mail zeigt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_template'] = ['Vorlage', 'Vorlage der E-Mail. Eigene Fassungen im Ordner templates/ mit dem Namensanfang „counter_mail_“ erscheinen hier.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absender'] = ['Absenderadresse', 'Ohne Angabe wird die Administrator-Adresse aus den Contao-Einstellungen verwendet.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absendername'] = ['Absendername', 'Name, der als Absender erscheint.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_betreff'] = ['Zusatz vor dem Betreff', 'Fester Text, der jeder Betreffzeile vorangestellt wird, etwa „[Webstatistik]“.'];

$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_page'] = ['Empfänger der Seitenstatistik', 'Eine Adresse je Zeile oder durch Komma getrennt. Erlaubt ist auch die Schreibweise „Name &lt;adresse@example.org&gt;“. Ohne Empfänger wird die Seitenstatistik nicht verschickt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_page'] = ['Seitenstatistik in Kopie', 'Weitere Adressen, die eine Kopie der Seitenstatistik erhalten. Gleiche Schreibweise wie nebenan.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_article'] = ['Empfänger der Artikelstatistik', 'Eine Adresse je Zeile oder durch Komma getrennt. Ohne Empfänger wird die Artikelstatistik nicht verschickt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_article'] = ['Artikelstatistik in Kopie', 'Weitere Adressen, die eine Kopie der Artikelstatistik erhalten.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_news'] = ['Empfänger der Nachrichtenstatistik', 'Eine Adresse je Zeile oder durch Komma getrennt. Ohne Empfänger wird die Nachrichtenstatistik nicht verschickt.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_news'] = ['Nachrichtenstatistik in Kopie', 'Weitere Adressen, die eine Kopie der Nachrichtenstatistik erhalten.'];
