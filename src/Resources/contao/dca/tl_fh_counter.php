<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 *
 * Tabelle tl_fh_counter — je Inhalt ein Zähler.
 *
 * Die Tabelle wird nie im Backend bearbeitet, deshalb enthält der DCA nur die
 * Feldbeschreibungen für den Datenbankabgleich (contao:migrate).
 */

$GLOBALS['TL_DCA']['tl_fh_counter'] = [

	'config' => [
		'sql' => [
			'keys' => [
				'id' => 'primary',
				// Verbundindex über beide Bedingungen der Zählerabfrage. Vorher
				// gab es zwei Einzelindizes, die MySQL zusammenführen musste;
				// jetzt findet die Abfrage den Datensatz mit einem Zugriff
				'source,pid' => 'index',
			],
		],
	],

	'fields' => [

		'id' => [
			'sql' => "int(10) unsigned NOT NULL auto_increment",
		],

		// Zeitstempel des letzten Schreibzugriffs
		'tstamp' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],

		// Erststart dieses Zählers
		'starttime' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],

		// Quelltabelle: tl_page, tl_article oder tl_news
		'source' => [
			'sql' => "varchar(64) NOT NULL default ''",
		],

		// ID des Inhalts in seiner Quelltabelle
		'pid' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],

		// Gesamtzugriffe, damit für die reine Summe das Zählerarray nicht
		// entpackt werden muss
		'totalhits' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],

		// Zuletzt gesehene IP-Adresse
		'lastip' => [
			'sql' => "varchar(50) NOT NULL default ''",
		],

		// Zeitpunkt der letzten gewerteten Zählung
		'lastcounting' => [
			'sql' => "int(10) unsigned NOT NULL default '0'",
		],

		// Bestmarke gleichzeitiger Besucher (serialisiertes Array)
		'toponline' => [
			'sql' => "text NULL",
		],

		// IP-Adressen innerhalb der Sperrzeit (serialisiertes Array)
		'iparray' => [
			'sql' => "text NULL",
		],

		// Zählstände nach Jahr/Monat/Tag/Stunde (serialisiertes Array)
		'counter' => [
			'sql' => "mediumtext NULL",
		],

		// Besucher innerhalb der Onlinezeit (serialisiertes Array)
		'online' => [
			'sql' => "text NULL",
		],
	],
];
