<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

use Contao\Database;
use Contao\StringUtil;

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
	 *
	 * @phpstan-param list<Pfad>        $pfade
	 * @phpstan-param list<Achsenpunkt> $verlauf
	 *
	 * @return array Drei Schlüssel:
	 *               zeilen  — Rangliste mit platz, hits, id, titel, zusatz, tstamp, datum, css
	 *               gesamt  — Summe aller Zugriffe im Zeitraum
	 *               verlauf — Liste aus titel und wert, in der Reihenfolge der Achse
	 *
	 * @phpstan-return Ergebnis
	 */
	public static function auswerten(string $quelle, array $pfade, int $anzahl, array $verlauf = []): array
	{
		// Nur die beiden gebrauchten Spalten holen. Ein SELECT * zöge hier
		// zusätzlich die Online- und IP-Listen aller Zähler heran
		$zaehler = Database::getInstance()
			->prepare('SELECT pid, counter FROM tl_fh_counter WHERE source=? AND counter IS NOT NULL')
			->execute($quelle);

		$treffer = [];
		$gesamt = 0;
		$summen = array_fill(0, \count($verlauf), 0);

		while ($zaehler->next())
		{
			$counter = StringUtil::deserialize($zaehler->counter, true);

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
				$treffer[(int) $zaehler->pid] = $hits;
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
