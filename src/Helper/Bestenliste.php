<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;

/**
 * Wertet die Zählertabelle für einen Zeitraum aus.
 *
 * Die Zählstände liegen in tl_fh_counter als serialisiertes, nach
 * Jahr/Monat/Tag/Stunde geschachteltes Array. Um daraus eine Rangliste zu
 * bauen, muss jeder Datensatz einmal entpackt werden — deshalb läuft diese
 * Klasse genau einmal über die Tabelle und erledigt dabei beides zugleich:
 * die Rangliste und die Summen für ein Verlaufsdiagramm.
 *
 * Zeiträume werden als „Pfade“ in das Zählerarray beschrieben. Ein Tag ist
 * [[2026, 7, 30, 'all']], ein Monat [[2026, 7, 'all']], eine Woche schlicht
 * sieben Tagespfade hintereinander. Damit deckt eine Methode alles ab, was
 * Backend-Statistik und Statistik-Mail brauchen.
 *
 * Die hier festgelegten Typnamen beschreiben die Datenstruktur, die durch das
 * ganze Bundle wandert — von der Auswertung über die Backend-Ansicht bis in
 * die E-Mail. Andere Klassen holen sie sich per @phpstan-import-type, damit
 * die Beschreibung an genau einer Stelle steht.
 *
 * @phpstan-type Pfad list<int|string>
 * @phpstan-type Achsenpunkt array{titel: string, pfad: Pfad}
 * @phpstan-type Verlaufswert array{titel: string, wert: int}
 * @phpstan-type Zeile array{platz: int, hits: int, id: int, titel: string, zusatz: string, tstamp: int, datum: string, css: string}
 * @phpstan-type Ergebnis array{zeilen: list<Zeile>, gesamt: int, verlauf: list<Verlaufswert>}
 */
final class Bestenliste
{
	/**
	 * Zahl der counter-Blöcke, die gleichzeitig geladen werden.
	 *
	 * Ein Block ist auf einer gewachsenen Website einige hundert Kilobyte
	 * groß, bei einer jahrelang stündlich besuchten Startseite gut ein
	 * Megabyte. 25 Blöcke halten den Speicher damit im zweistelligen
	 * Megabytebereich; mehr bringt bei der Geschwindigkeit nichts Messbares.
	 */
	private const PAKET = 25;

	/**
	 * Baut Rangliste und Verlaufswerte einer Quelle für einen Zeitraum.
	 *
	 * @param string $quelle  Tabellenname: tl_page, tl_article oder tl_news
	 * @param array  $pfade   Liste von Pfaden ins Zählerarray, deren Werte
	 *                        addiert werden. Ein leerer Pfad ergibt 0 Treffer
	 * @param int    $anzahl  Höchstzahl der Zeilen in der Rangliste
	 * @param array  $verlauf Optionale Achse für ein Diagramm: Liste aus
	 *                        ['titel' => Beschriftung, 'pfad' => Pfad]. Die
	 *                        Werte werden über ALLE Inhalte addiert, nicht nur
	 *                        über die der Rangliste
	 * @param int    $seit    Zeitstempel des Zeitraumbeginns. Zähler, die
	 *                        seitdem nicht mehr angefasst wurden, werden gar
	 *                        nicht erst geladen — siehe Begründung im Rumpf.
	 *                        null wertet alle Zähler aus
	 * @param array  $nurIds  Beschränkt die Auswertung auf diese Inhalte,
	 *                        etwa auf die Seiten eines Seitenpfads. Das gilt
	 *                        für Rangliste, Gesamtsumme UND Verlauf. null
	 *                        wertet alle Inhalte der Quelle aus, eine leere
	 *                        Liste ergibt ein leeres Ergebnis
	 *
	 * @phpstan-param list<Pfad>        $pfade
	 * @phpstan-param list<Achsenpunkt> $verlauf
	 * @phpstan-param list<int>|null    $nurIds
	 *
	 * @return array Drei Schlüssel:
	 *               zeilen  — Rangliste mit platz, hits, id, titel, zusatz, tstamp, datum, css
	 *               gesamt  — Summe aller Zugriffe im Zeitraum
	 *               verlauf — Liste aus titel und wert, in der Reihenfolge der Achse
	 *
	 * @phpstan-return Ergebnis
	 */
	public static function auswerten(string $quelle, array $pfade, int $anzahl, array $verlauf = [], ?int $seit = null, ?array $nurIds = null): array
	{
		if ([] === $nurIds)
		{
			$achse = [];

			foreach ($verlauf as $punkt)
			{
				$achse[] = ['titel' => $punkt['titel'], 'wert' => 0];
			}

			return ['zeilen' => [], 'gesamt' => 0, 'verlauf' => $achse];
		}

		// Nur Zähler, die überhaupt einen Zählerblock haben
		$bedingung = 'source=? AND counter IS NOT NULL';
		$parameter = [$quelle];

		// Zähler ausschließen, die im Zeitraum gar nicht angefasst wurden.
		//
		// tstamp ist der letzte Schreibzugriff, und geschrieben wird bei jedem
		// Besuch. Ein Zähler mit Zugriffen im Zeitraum muss also während des
		// Zeitraums geschrieben worden sein — sein tstamp liegt damit
		// zwangsläufig am Anfang des Zeitraums oder danach. Wer davor
		// stehengeblieben ist, kann im Zeitraum nichts gezählt haben.
		//
		// Wie viel das spart, hängt vom Besucherverhalten ab: Werden alte
		// Inhalte kaum noch aufgerufen, entfällt der Löwenanteil. Auf
		// schachbund.de rufen Suchmaschinen dagegen fast jede Nachricht täglich
		// auf (gemessen am 15.11.2026: rund 17 000 von 17 676 Nachrichtenzählern
		// innerhalb von drei Tagen angefasst) — dort bleibt kaum etwas übrig.
		if (null !== $seit)
		{
			$bedingung .= ' AND tstamp >= ?';
			$parameter[] = $seit;
		}

		// Die Beschränkung gehört in die Abfrage, nicht hinter sie: Das Teure
		// ist das Laden und Entpacken der counter-Blöcke, und das soll für
		// Inhalte außerhalb der Auswahl gar nicht erst anfallen.
		//
		// Die IDs stehen als Zahlen direkt in der Abfrage statt als
		// Platzhalter. Ein ganzer Seitenbaum kann einige tausend Seiten
		// umfassen, und MySQL erlaubt je Abfrage höchstens 65 535 Platzhalter.
		// Durch intval() ist das Einsetzen unbedenklich.
		if (null !== $nurIds)
		{
			$bedingung .= ' AND pid IN ('.implode(',', array_map('intval', $nurIds)).')';
		}

		$treffer = [];
		$gesamt = 0;
		$summen = array_fill(0, \count($verlauf), 0);

		foreach (self::zaehler($bedingung, $parameter) as $zeile)
		{
			$counter = StringUtil::deserialize($zeile['counter'], true);

			if (!$counter)
			{
				continue;
			}

			$hits = 0;

			foreach ($pfade as $pfad)
			{
				$hits += self::wert($counter, $pfad);
			}

			if ($hits > 0)
			{
				$treffer[(int) $zeile['pid']] = $hits;
				$gesamt += $hits;
			}

			foreach ($verlauf as $i => $punkt)
			{
				$summen[$i] += self::wert($counter, $punkt['pfad']);
			}
		}

		arsort($treffer);
		$treffer = \array_slice($treffer, 0, max(1, $anzahl), true);

		$zeilen = [];

		if ($treffer)
		{
			$details = Inhalte::details($quelle, array_keys($treffer));
			$platz = 1;

			foreach ($treffer as $id => $hits)
			{
				if (!isset($details[$id]))
				{
					// Inhalt zwischenzeitlich gelöscht, Zähler verwaist
					continue;
				}

				$zeilen[] = [
					'platz'  => $platz,
					'hits'   => $hits,
					'id'     => $id,
					'titel'  => $details[$id]['titel'],
					'zusatz' => $details[$id]['zusatz'],
					'tstamp' => $details[$id]['tstamp'],
					'datum'  => $details[$id]['tstamp'] ? date('d.m.Y H:i', $details[$id]['tstamp']) : '',
					'css'    => ($platz % 2) ? 'odd' : 'even',
				];

				++$platz;
			}
		}

		$achse = [];

		foreach ($verlauf as $i => $punkt)
		{
			$achse[] = ['titel' => $punkt['titel'], 'wert' => $summen[$i]];
		}

		return ['zeilen' => $zeilen, 'gesamt' => $gesamt, 'verlauf' => $achse];
	}

	/**
	 * Liefert die Zählerblöcke einer Abfrage, ohne sie alle zugleich zu laden.
	 *
	 * Eine gewöhnliche Abfrage über alle Zähler hält den Speicher NICHT klein,
	 * auch nicht mit fetchAssociative() in einer Schleife: Der PDO-Treiber für
	 * MySQL puffert die komplette Ergebnismenge, bevor die erste Zeile
	 * herauskommt. Gemessen am 15.11.2026 an 900 Zählern mit zusammen 663 MB:
	 * Spitze 678 MB. Version 2.4.2 hatte das übersehen, weil dort der Speicher
	 * nach dem Aufruf gemessen wurde statt der Spitze.
	 *
	 * Ein überschrittenes memory_limit ist ein PHP-Fatal. Er steht in keinem
	 * Protokoll, der Cronjob gilt trotzdem als erledigt — und weil die
	 * Nachrichten als Letzte drankommen und den größten Bestand haben, fehlte
	 * immer genau ihre Mail.
	 *
	 * Deshalb zwei Wege, beide mit festem Speicherbedarf:
	 *
	 * 1. Ungepuffert, wo es geht: MySQL liefert die Zeilen nach, während PHP
	 *    sie verarbeitet. Gemessen an denselben 900 Zählern: Spitze 12,9 MB und
	 *    mit 4,8 s sogar schneller als bisher (8,1 s), weil Übertragung und
	 *    Entpacken gleichzeitig laufen.
	 * 2. In Paketen zu PAKET Zählern, wo der ungepufferte Weg nicht in Frage
	 *    kommt (siehe ungepuffertesPdo()). Spitze 38,5 MB, dafür langsamer
	 *    (12,7 s), weil jedes Paket eine eigene Abfrage ist.
	 *
	 * Solange der Generator läuft, darf über dieselbe Verbindung keine andere
	 * Abfrage gestellt werden — das ist beim ungepufferten Weg eine Vorgabe von
	 * MySQL. Der Aufrufer entpackt in der Schleife nur und fragt nichts ab.
	 *
	 * @param string $bedingung WHERE-Klausel ohne das Wort WHERE, mit
	 *                          Platzhaltern für $parameter
	 * @param array  $parameter Werte für die Platzhalter
	 *
	 * @phpstan-param list<int|string> $parameter
	 *
	 * @return \Generator Je Zähler ein Array mit pid und counter
	 *
	 * @phpstan-return \Generator<int, array<string, mixed>, mixed, void>
	 */
	private static function zaehler(string $bedingung, array $parameter): \Generator
	{
		// Bewusst über Doctrine statt über Contao\Database: Contaos
		// Database\Result hält jede gelesene Zeile dauerhaft fest
		// (Result::preload hängt sie an $resultSet an und gibt sie nie frei)
		$verbindung = System::getContainer()->get('database_connection');

		if (!$verbindung instanceof Connection)
		{
			throw new \RuntimeException('Counter: Die Datenbankverbindung (database_connection) ist nicht verfügbar.');
		}

		$pdo = self::ungepuffertesPdo($verbindung);

		if (null !== $pdo)
		{
			$schalter = self::pufferSchalter();
			$vorher = $pdo->getAttribute($schalter);
			$ergebnis = null;

			$pdo->setAttribute($schalter, false);

			try
			{
				$ergebnis = $verbindung->executeQuery('SELECT pid, counter FROM tl_fh_counter WHERE '.$bedingung, $parameter);

				while (false !== ($zeile = $ergebnis->fetchAssociative()))
				{
					yield $zeile;
				}
			}
			finally
			{
				// Auch bei einem Abbruch mittendrin: Restzeilen verwerfen und den
				// Puffer wieder einschalten, sonst scheitert jede folgende
				// Abfrage dieser Anfrage an „Commands out of sync“
				if (null !== $ergebnis)
				{
					$ergebnis->free();
				}

				$pdo->setAttribute($schalter, $vorher);
			}

			return;
		}

		// Erst nur die IDs — eine Liste von Zahlen, die MySQL aus dem Index
		// beantwortet, ohne einen einzigen counter-Block anzufassen
		$ids = array_map('intval', $verbindung->fetchFirstColumn('SELECT id FROM tl_fh_counter WHERE '.$bedingung, $parameter));

		foreach (array_chunk($ids, self::PAKET) as $paket)
		{
			foreach ($verbindung->fetchAllAssociative('SELECT pid, counter FROM tl_fh_counter WHERE id IN ('.implode(',', $paket).')') as $zeile)
			{
				yield $zeile;
			}
		}
	}

	/**
	 * Prüft, ob die Zählerblöcke ungepuffert gelesen werden können.
	 *
	 * Drei Voraussetzungen:
	 *
	 * - Die Verbindung läuft über PDO. Doctrine kann auch mysqli verwenden;
	 *   dort gibt es den Schalter nicht.
	 * - Der PDO-Treiber ist der für MySQL bzw. MariaDB.
	 * - tl_fh_counter liegt in InnoDB. Eine MyISAM-Tabelle aus alten
	 *   Installationen sperrt die ganze Tabelle, solange gelesen wird — bei
	 *   einem ungepufferten Lesevorgang also für die gesamte Auswertung. In
	 *   dieser Zeit könnte das Frontend keinen einzigen Zugriff zählen und
	 *   jeder Seitenaufruf bliebe hängen. InnoDB liest dagegen ohne Sperre.
	 *
	 * @param Connection $verbindung Doctrine-Verbindung von Contao
	 *
	 * @return \PDO|null Die PDO-Verbindung, oder null, wenn eine der
	 *                   Voraussetzungen fehlt — dann gilt der Paketweg
	 */
	private static function ungepuffertesPdo(Connection $verbindung): ?\PDO
	{
		// getNativeConnection() gibt es ab Doctrine DBAL 3.3 — so viel verlangt
		// schon Contao 4.13, eine Prüfung darauf ist deshalb überflüssig
		$nativ = $verbindung->getNativeConnection();

		if (!$nativ instanceof \PDO || 'mysql' !== $nativ->getAttribute(\PDO::ATTR_DRIVER_NAME))
		{
			return null;
		}

		$engine = $verbindung->fetchOne(
			"SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tl_fh_counter'"
		);

		return \is_string($engine) && 'innodb' === strtolower($engine) ? $nativ : null;
	}

	/**
	 * Liefert die Nummer des PDO-Attributs für gepufferte MySQL-Abfragen.
	 *
	 * Ab PHP 8.4 steht die Konstante an der Klasse Pdo\Mysql; die alte Form
	 * PDO::MYSQL_ATTR_USE_BUFFERED_QUERY gilt ab PHP 8.5 als veraltet. Beide
	 * haben denselben Wert, deshalb genügt es, die neue zu bevorzugen.
	 *
	 * @return int
	 */
	private static function pufferSchalter(): int
	{
		$neu = 'Pdo\Mysql::ATTR_USE_BUFFERED_QUERY';

		return \defined($neu) ? (int) \constant($neu) : \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
	}

	/**
	 * Liest einen Wert aus dem verschachtelten Zählerarray.
	 *
	 * @param array $counter Deserialisiertes Zählerarray eines Datensatzes
	 * @param array $pfad    Schlüsselfolge, etwa [2026, 7, 30, 'all']
	 *
	 * @phpstan-param array<int|string, mixed> $counter
	 * @phpstan-param Pfad                     $pfad
	 *
	 * @return int Gefundener Wert oder 0, wenn der Pfad ins Leere führt.
	 *             Fehlende Zweige sind der Normalfall: ein Zähler enthält nur
	 *             die Tage, an denen er auch etwas gezählt hat
	 */
	private static function wert(array $counter, array $pfad): int
	{
		$knoten = $counter;

		foreach ($pfad as $schluessel)
		{
			if (!\is_array($knoten) || !isset($knoten[$schluessel]))
			{
				return 0;
			}

			$knoten = $knoten[$schluessel];
		}

		return is_numeric($knoten) ? (int) $knoten : 0;
	}
}
