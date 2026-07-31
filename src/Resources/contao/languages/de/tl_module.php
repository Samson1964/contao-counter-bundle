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
$GLOBALS['TL_LANG']['tl_module']['counter_legend'] = 'Zähler-Einstellungen';
$GLOBALS['TL_LANG']['tl_module']['info_legend'] = 'Ausgabe-Einstellungen';

/**
 * Felder des Zählermoduls
 */
$GLOBALS['TL_LANG']['tl_module']['fhc_register_pages'] = ['Seitenzugriffe zählen', 'Zugriffe auf Seiten (tl_page) zählen.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_register_articles'] = ['Artikelzugriffe zählen', 'Zugriffe auf Artikel (tl_article) zählen. Getrennt gezählt wird nur, wer einen Artikel eigens aufruft.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_register_news'] = ['Nachrichtenzugriffe zählen', 'Zugriffe auf Nachrichten (tl_news) zählen.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_onlinetime'] = ['Onlinezeit in Sekunden', 'Nach dieser Zeit gilt ein Besucher als nicht mehr online.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_registernewtime'] = ['Zählsperre in Sekunden', 'Kehrt ein Besucher innerhalb dieser Zeit zurück, wird er nicht erneut gezählt.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_register_be_user'] = ['Backend-Benutzer mitzählen', 'Zugriffe von Benutzern zählen, die im Backend angemeldet sind. Ohne Haken bleiben Redakteure außen vor.'];

/**
 * Felder des Ausgabemoduls
 */
$GLOBALS['TL_LANG']['tl_module']['fhc_infos_counter'] = ['Allgemeine Informationen', 'Angaben zu diesem Zähler anzeigen, etwa Erstaufruf und Gesamtzugriffe.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_infos_debug'] = ['Diagnose-Informationen', 'Diagnoseangaben zu diesem Zähler anzeigen, etwa die Adressen der Besucher.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_pages'] = ['Seitenzugriffe', 'Zugriffe auf Seiten anzeigen.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_articles'] = ['Artikelzugriffe', 'Zugriffe auf Artikel anzeigen.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_news'] = ['Nachrichtenzugriffe', 'Zugriffe auf Nachrichten anzeigen.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_diagrams'] = ['Diagramme', 'Verlauf des Standardzählers als Diagramm anzeigen (letzte 24 Stunden, 30 Tage, 12 Monate).'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_tables'] = ['Tabellen', 'Zugriffe aller Zähler als Tabellen anzeigen.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_template'] = ['Template auswählen', 'Template für die Ausgabe. Eigene Fassungen im Ordner templates/ mit dem Namensanfang „fhcounter_“ erscheinen hier.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_1000_separator'] = ['Tausender-Trennzeichen', 'Tausender im Zähler mit einem Punkt trennen.'];
