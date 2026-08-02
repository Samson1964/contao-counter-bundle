<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

use Contao\Input;
use Contao\Template;

/**
 * Bereitet die gezählten Werte für ein Frontend-Template auf.
 *
 * Das Zählwerk legt seine Ergebnisse in $GLOBALS['fhcounter'] ab, getrennt
 * nach Quelle (tl_page, tl_article, tl_news) und zusätzlich unter „default“
 * für den zuletzt gezählten, also speziellsten Inhalt. Diese Klasse macht
 * daraus die Template-Variablen.
 *
 * Sie ersetzt vier fast wortgleiche Blöcke, die früher sowohl im
 * Ausgabemodul als auch in der Insert-Tag-Klasse standen — insgesamt acht
 * Kopien derselben Zuweisungen.
 */
final class Auswertung
{
	/**
	 * Zuordnung Quelle -> Präfix der Template-Variablen.
	 *
	 * Der Standardzähler bekommt kein Präfix: CounterAll, während die Seite
	 * PageCounterAll heißt.
	 */
	private const PRAEFIXE = [
		'default'    => '',
		'tl_page'    => 'Page',
		'tl_article' => 'Article',
		'tl_news'    => 'News',
	];

	/**
	 * Zählwerte, die bei eingeschaltetem Tausendertrennzeichen umformatiert werden
	 */
	private const ZAHLENFELDER = [
		'All', 'ThisYear', 'ThisMonth', 'ThisDay', 'ThisHour', 'Yesterday',
		'LastMonth', 'Totalhits', 'Online', 'TopOnlineCount', 'Average',
	];

	/**
	 * Einstellungen des Ausgabemoduls
	 *
	 * @var array<string, bool>
	 */
	private array $optionen;

	/**
	 * Aufrufzeitpunkt, einmal zerlegt
	 */
	private int $zeit;

	/**
	 * Nimmt die Einstellungen des Ausgabemoduls entgegen.
	 *
	 * @param array $optionen Erwartete Schlüssel (alle optional, Vorgabe false):
	 *                        pages, articles, news — welche Zähler ausgegeben werden;
	 *                        infos, debug, diagramme, tabellen — welche Zusatzblöcke;
	 *                        trennzeichen — Tausenderpunkte setzen
	 *
	 * @phpstan-param array<string, bool> $optionen
	 */
	public function __construct(array $optionen = [])
	{
		$this->optionen = array_merge([
			'pages'        => false,
			'articles'     => false,
			'news'         => false,
			'infos'        => false,
			'debug'        => false,
			'diagramme'    => false,
			'tabellen'     => false,
			'trennzeichen' => false,
		], $optionen);

		$this->zeit = time();
	}

	/**
	 * Schreibt sämtliche Zählerwerte in das übergebene Template.
	 *
	 * Für jede Quelle wird geprüft, ob überhaupt Zahlen vorliegen. Fehlen sie
	 * (etwa weil auf dieser Seite gar keine Nachricht angezeigt wird), wird
	 * das zugehörige View-Kennzeichen auf false gesetzt, damit das Template
	 * den Block überspringt statt Nullen auszugeben.
	 *
	 * @param Template $template Ziel-Template, üblicherweise ein FrontendTemplate
	 *
	 * @return void Seiteneffekt: setzt zahlreiche Eigenschaften am Template
	 */
	public function fuelleTemplate(Template $template): void
	{
		$template->ViewPages = (bool) $this->optionen['pages'];
		$template->ViewArticles = (bool) $this->optionen['articles'];
		$template->ViewNews = (bool) $this->optionen['news'];
		$template->ViewDefault = $template->ViewPages || $template->ViewArticles || $template->ViewNews;
		$template->ViewCounterinfo = (bool) $this->optionen['infos'];
		$template->ViewDebuginfo = (bool) $this->optionen['debug'];
		$template->ViewDiagrams = (bool) $this->optionen['diagramme'];
		$template->ViewTables = (bool) $this->optionen['tabellen'];

		$anzeigen = [
			'default'    => 'ViewDefault',
			'tl_page'    => 'ViewPages',
			'tl_article' => 'ViewArticles',
			'tl_news'    => 'ViewNews',
		];

		foreach (self::PRAEFIXE as $quelle => $praefix)
		{
			$kennzeichen = $anzeigen[$quelle];

			if (!$template->$kennzeichen || empty($GLOBALS['fhcounter'][$quelle]))
			{
				$template->$kennzeichen = false;
				continue;
			}

			$this->schreibeWerte($template, $praefix, $GLOBALS['fhcounter'][$quelle]);
		}

		if ($template->ViewDebuginfo)
		{
			$template->Debuginfo = $this->debugdaten();
		}

		if ($template->ViewDiagrams)
		{
			$this->schreibeDiagramme($template);
		}
	}

	/**
	 * Schreibt die Werte einer einzelnen Quelle mit ihrem Präfix ins Template.
	 *
	 * @param Template $template Ziel-Template
	 * @param string   $praefix  '' , 'Page', 'Article' oder 'News'
	 * @param array    $daten    Ein Eintrag aus $GLOBALS['fhcounter']
	 *
	 * @phpstan-param array<string, mixed> $daten
	 *
	 * @return void
	 */
	private function schreibeWerte(Template $template, string $praefix, array $daten): void
	{
		$counter = \is_array($daten['counter']) ? $daten['counter'] : [];

		$jahr = date('Y', $this->zeit);
		$monat = date('n', $this->zeit);
		$tag = date('j', $this->zeit);
		$stunde = date('G', $this->zeit);

		$gestern = $this->zeit - 86400;
		$gJahr = date('Y', $gestern);
		$gMonat = date('n', $gestern);
		$gTag = date('j', $gestern);

		$vormonat = strtotime('last month', $this->zeit);
		$vJahr = date('Y', $vormonat);
		$vMonat = date('n', $vormonat);

		$werte = [
			'All'            => $counter['all'] ?? 0,
			'ThisYear'       => $counter[$jahr]['all'] ?? 0,
			'ThisMonth'      => $counter[$jahr][$monat]['all'] ?? 0,
			'ThisDay'        => $counter[$jahr][$monat][$tag]['all'] ?? 0,
			'ThisHour'       => $counter[$jahr][$monat][$tag][$stunde] ?? 0,
			'Yesterday'      => $counter[$gJahr][$gMonat][$gTag]['all'] ?? 0,
			'LastMonth'      => $counter[$vJahr][$vMonat]['all'] ?? 0,
			'Tstamp'         => $daten['tstamp'],
			'Starttime'      => $daten['starttime'],
			'Source'         => $daten['source'],
			'Pid'            => $daten['pid'],
			'Totalhits'      => $daten['totalhits'],
			'Lastip'         => $daten['lastip'],
			'Lastcounting'   => $daten['lastcounting'],
			'Online'         => $daten['online'],
			'TopOnlineCount' => $daten['toponline']['count'] ?? 0,
			'TopOnlineTime'  => $daten['toponline']['time'] ?? 0,
			'Average'        => self::durchschnitt((int) $daten['totalhits'], (int) $daten['starttime'], (int) $daten['tstamp']),
			'Check'          => $daten['counting'],
		];

		foreach ($werte as $name => $wert)
		{
			// Zahlenwerte auf Wunsch mit deutschen Tausenderpunkten
			if ($this->optionen['trennzeichen'] && \in_array($name, self::ZAHLENFELDER, true))
			{
				$wert = number_format((float) $wert, 0, ',', '.');
			}

			$template->{$praefix.'Counter'.$name} = $wert;
		}
	}

	/**
	 * Errechnet die durchschnittlichen Zugriffe je Tag.
	 *
	 * @param int $gesamt    Gesamtzahl der Zugriffe
	 * @param int $startzeit Zeitstempel der ersten Zählung
	 * @param int $letzter   Zeitstempel des letzten Aufrufs
	 *
	 * @return int Zugriffe je Tag, kaufmännisch gerundet. 0, solange der
	 *             Zähler keinen vollen Tag alt ist — sonst käme durch die
	 *             Division durch einen Bruchteil eines Tages ein absurd
	 *             hoher Wert heraus
	 */
	private static function durchschnitt(int $gesamt, int $startzeit, int $letzter): int
	{
		$tage = ($letzter - $startzeit) / 86400;

		if ($tage < 1)
		{
			return $gesamt;
		}

		return (int) round($gesamt / $tage);
	}

	/**
	 * Stellt die Diagnosewerte für die Debug-Ausgabe zusammen.
	 *
	 * @return array Verschachteltes Array: Abschnitt -> Bezeichnung -> Wert
	 *
	 * @phpstan-return array<string, array<string, scalar|null>>
	 */
	private function debugdaten(): array
	{
		$debug = [
			'head' => [
				'SERVER REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
				'GET articles'       => Input::get('articles'),
			],
		];

		foreach (['tl_page', 'tl_article', 'tl_news'] as $quelle)
		{
			if (empty($GLOBALS['fhcounter'][$quelle]))
			{
				continue;
			}

			$daten = $GLOBALS['fhcounter'][$quelle];

			$debug[$quelle] = [
				'Besucher gezählt'         => $daten['counting'] ? 'Ja' : 'Nein',
				'Letzte Aktualisierung'    => date('d.m.Y H:i:s', (int) $daten['tstamp']),
				'Erststart Zähler'         => date('d.m.Y H:i:s', (int) $daten['starttime']),
				'Name der Quelltabelle'    => $daten['source'],
				'ID in Quelltabelle'       => $daten['pid'],
				'Gesamtzugriffe'           => $daten['totalhits'],
				'Letzte Zählung'           => date('d.m.Y H:i:s', (int) $daten['lastcounting']),
				'Letzter Besucher'         => $daten['lastip'],
				'Topbesucher gleichzeitig' => ($daten['toponline']['count'] ?? 0).' am '.date('d.m.Y H:i:s', (int) ($daten['toponline']['time'] ?? 0)),
			];
		}

		return $debug;
	}

	/**
	 * Erzeugt die drei Verlaufsdiagramme des Standardzählers.
	 *
	 * Ausgegeben wird fertiges SVG. Früher standen hier Datenreihen im Format
	 * der Javascript-Bibliothek „flot“, die im Template zusammengesetzt
	 * wurden — die Bibliothek ist entfallen, die Diagramme entstehen jetzt auf
	 * dem Server.
	 *
	 * @param Template $template Ziel-Template
	 *
	 * @return void Setzt CounterHoursChart, CounterDaysChart und
	 *              CounterMonthsChart sowie die zugehörigen Anzahl-Variablen
	 */
	private function schreibeDiagramme(Template $template): void
	{
		$counter = $GLOBALS['fhcounter']['default']['counter'] ?? [];

		if (!\is_array($counter))
		{
			$counter = [];
		}

		// Letzte 24 Stunden
		$stunden = [];

		for ($x = 23; $x >= 0; --$x)
		{
			$zeitpunkt = $this->zeit - $x * 3600;
			$jahr = date('Y', $zeitpunkt);
			$monat = date('n', $zeitpunkt);
			$tag = date('j', $zeitpunkt);
			$stunde = date('G', $zeitpunkt);

			$stunden[] = [
				'titel' => $stunde,
				'wert'  => $counter[$jahr][$monat][$tag][$stunde] ?? 0,
			];
		}

		// Letzte 30 Tage
		$tage = [];

		for ($x = 29; $x >= 0; --$x)
		{
			$zeitpunkt = $this->zeit - $x * 86400;
			$jahr = date('Y', $zeitpunkt);
			$monat = date('n', $zeitpunkt);
			$tag = date('j', $zeitpunkt);

			$tage[] = [
				'titel' => $tag,
				'wert'  => $counter[$jahr][$monat][$tag]['all'] ?? 0,
			];
		}

		// Letzte 12 Monate, gerechnet ab dem Ersten des laufenden Monats
		$monate = [];
		$monatsanfang = mktime(0, 0, 0, (int) date('n', $this->zeit), 1, (int) date('Y', $this->zeit));

		for ($x = 11; $x >= 0; --$x)
		{
			$zeitpunkt = strtotime('-'.$x.' months', (int) $monatsanfang);
			$jahr = date('Y', $zeitpunkt);
			$monat = date('n', $zeitpunkt);

			$monate[] = [
				'titel' => $monat,
				'wert'  => $counter[$jahr][$monat]['all'] ?? 0,
			];
		}

		$template->CounterHoursChart = Diagramm::balken($stunden, 'Zugriffe je Stunde');
		$template->CounterDaysChart = Diagramm::balken($tage, 'Zugriffe je Tag', Diagramm::FARBE);
		$template->CounterMonthsChart = Diagramm::balken($monate, 'Zugriffe je Monat', Diagramm::FARBE);
		$template->CounterHoursValues = \count($stunden);
		$template->CounterDaysValues = \count($tage);
		$template->CounterMonthsValues = \count($monate);
	}
}
