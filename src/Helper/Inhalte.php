<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

use Contao\Config;
use Contao\Database;

/**
 * Wissen über die drei gezählten Inhaltsarten an einer Stelle.
 *
 * Seiten, Artikel und Nachrichten liegen in verschiedenen Tabellen mit
 * verschiedenen Spaltennamen. Damit weder die Backend-Statistik noch der
 * Cronjob für die Statistik-Mails das jeweils selbst wissen muss, steht die
 * Zuordnung hier gebündelt.
 *
 * @phpstan-type Detail array{titel: string, zusatz: string, tstamp: int}
 */
final class Inhalte
{
	/**
	 * Alle Quellen, die der Zähler kennt
	 *
	 * @var list<string>
	 */
	public const QUELLEN = ['tl_page', 'tl_article', 'tl_news'];

	/**
	 * Beschreibung der drei Quellen.
	 *
	 * modul    Bezeichner des Backend-Moduls für die Adresszeile (do=…)
	 * name     Mehrzahl für Überschriften
	 * einzahl  Einzahl für Fließtext
	 * zusatz   Überschrift der Zusatzspalte in den Ranglisten
	 * topx     Name der Einstellung mit der Zahl der Listeneinträge
	 */
	private const ARTEN = [
		'tl_page' => [
			'modul'   => 'page',
			'name'    => 'Seiten',
			'einzahl' => 'diese Seite',
			'zusatz'  => 'Alias',
			'topx'    => 'counter_topx_pages',
		],
		'tl_article' => [
			'modul'   => 'article',
			'name'    => 'Artikel',
			'einzahl' => 'diesen Artikel',
			'zusatz'  => 'Seite',
			'topx'    => 'counter_topx_articles',
		],
		'tl_news' => [
			'modul'   => 'news',
			'name'    => 'Nachrichten',
			'einzahl' => 'diese Nachricht',
			'zusatz'  => 'Archiv',
			'topx'    => 'counter_topx_news',
		],
	];

	/**
	 * Prüft, ob eine Quelle dem Zähler bekannt ist.
	 *
	 * @param string $quelle Tabellenname, etwa tl_page
	 *
	 * @return bool
	 */
	public static function bekannt(string $quelle): bool
	{
		return isset(self::ARTEN[$quelle]);
	}

	/**
	 * Liefert eine einzelne Eigenschaft einer Quelle.
	 *
	 * @param string $quelle    Tabellenname, etwa tl_page
	 * @param string $eigenschaft modul, name, einzahl, zusatz oder topx
	 *
	 * @return string Wert der Eigenschaft, leerer String bei unbekannter Quelle
	 */
	public static function eigenschaft(string $quelle, string $eigenschaft): string
	{
		return self::ARTEN[$quelle][$eigenschaft] ?? '';
	}

	/**
	 * Zahl der Einträge in den Ranglisten dieser Quelle.
	 *
	 * @param string $quelle Tabellenname, etwa tl_page
	 *
	 * @return int Wert aus den Contao-Einstellungen, ersatzweise 100
	 */
	public static function anzahl(string $quelle): int
	{
		$name = self::eigenschaft($quelle, 'topx');

		return (int) (($name ? Config::get($name) : 0) ?: 100);
	}

	/**
	 * Holt Bezeichnung, Zusatzangabe und Datum zu einer Liste von IDs.
	 *
	 * Wird bewusst erst aufgerufen, wenn die Rangliste feststeht: dann genügt
	 * eine einzige Abfrage für die tatsächlich angezeigten Zeilen. Früher lief
	 * eine eigene Abfrage je gezähltem Datensatz — bei einigen tausend
	 * Nachrichten also einige tausend Abfragen für am Ende 100 Zeilen.
	 *
	 * Inhalte, die es nicht mehr gibt, fehlen im Ergebnis. Der Aufrufer muss
	 * die Zeile dann überspringen; der zugehörige Zähler ist verwaist.
	 *
	 * @param string $quelle Tabellenname, etwa tl_page
	 * @param array  $ids    Liste der IDs. Eine leere Liste ergibt ein leeres Array
	 *
	 * @phpstan-param list<int> $ids
	 *
	 * @return array Zuordnung ID => ['titel' => string, 'zusatz' => string, 'tstamp' => int]
	 *
	 * @phpstan-return array<int, Detail>
	 */
	public static function details(string $quelle, array $ids): array
	{
		if (!$ids || !self::bekannt($quelle))
		{
			return [];
		}

		$platzhalter = implode(',', array_fill(0, \count($ids), '?'));

		switch ($quelle)
		{
			case 'tl_page':
				// Als Bezeichnung dient der Seitentitel aus den Metaangaben,
				// weil er auch in der Adressleiste und in Suchmaschinen steht;
				// fehlt er, wird der Name der Seite genommen
				$sql = 'SELECT id, tstamp, alias AS zusatz, IF(pageTitle!=\'\', pageTitle, title) AS titel
						  FROM tl_page WHERE id IN ('.$platzhalter.')';
				break;

			case 'tl_article':
				// Artikeltitel wie „Hauptartikel“ gibt es reihenweise, deshalb
				// steht in der Zusatzspalte die Seite, auf der er liegt
				$sql = 'SELECT a.id, a.tstamp, a.title AS titel, p.title AS zusatz
						  FROM tl_article a
					 LEFT JOIN tl_page p ON p.id = a.pid
						 WHERE a.id IN ('.$platzhalter.')';
				break;

			default:
				// Bei Nachrichten zählt das Veröffentlichungsdatum, nicht der
				// Zeitpunkt der letzten Bearbeitung
				$sql = 'SELECT n.id, n.date AS tstamp, n.headline AS titel, ar.title AS zusatz
						  FROM tl_news n
					 LEFT JOIN tl_news_archive ar ON ar.id = n.pid
						 WHERE n.id IN ('.$platzhalter.')';
				break;
		}

		$ergebnis = Database::getInstance()->prepare($sql)->execute(...$ids);

		$daten = [];

		while ($ergebnis->next())
		{
			$daten[(int) $ergebnis->id] = [
				'titel'  => (string) $ergebnis->titel,
				'zusatz' => (string) $ergebnis->zusatz,
				'tstamp' => (int) $ergebnis->tstamp,
			];
		}

		return $daten;
	}
}
