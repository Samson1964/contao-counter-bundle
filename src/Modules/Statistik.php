<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Modules;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Schachbulle\ContaoCounterBundle\Cron\Statistikmail;
use Schachbulle\ContaoCounterBundle\Helper\Bestenliste;
use Schachbulle\ContaoCounterBundle\Helper\Diagramm;
use Schachbulle\ContaoCounterBundle\Helper\Inhalte;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * Gemeinsame Grundlage der drei Backend-Statistiken.
 *
 * Seiten, Artikel und Nachrichten unterscheiden sich nur in der Quelltabelle;
 * alles Weitere — Zeitraumnavigation, Auswertung, Diagramm, Zwischenspeicher —
 * ist identisch und steht deshalb hier. Die Ableitungen nennen nur noch ihre
 * Quelle, den Rest holt sich diese Klasse aus Helper\Inhalte.
 *
 * Die Ansicht kennt drei Ebenen: ein Jahr, ein Monat oder ein einzelner Tag.
 * Das Diagramm zeigt jeweils die nächstfeinere Einteilung, also die zwölf
 * Monate eines Jahres, die Tage eines Monats oder die 24 Stunden eines Tages.
 *
 * @phpstan-import-type Pfad from Bestenliste
 * @phpstan-import-type Achsenpunkt from Bestenliste
 * @phpstan-import-type Zeile from Bestenliste
 * @phpstan-import-type Verlaufswert from Bestenliste
 * @phpstan-import-type Ergebnis from Bestenliste
 *
 * @phpstan-type AuswertungMitStand array{zeilen: list<Zeile>, gesamt: int, verlauf: list<Verlaufswert>, cacheDatum: string}
 */
abstract class Statistik
{
	/**
	 * Erlaubte Ebenen mit ihrer Beschriftung
	 */
	protected const EBENEN = [
		'jahr'  => 'Jahr',
		'monat' => 'Monat',
		'tag'   => 'Tag',
	];

	/**
	 * Kurznamen der Monate für die Diagrammbeschriftung
	 */
	protected const MONATE_KURZ = [
		1 => 'Jan', 2 => 'Feb', 3 => 'Mär', 4 => 'Apr', 5 => 'Mai', 6 => 'Jun',
		7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dez',
	];

	/**
	 * Volle Monatsnamen für die Zeitraumangabe
	 */
	protected const MONATE = [
		1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni',
		7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
	];

	/**
	 * Name der Quelltabelle in tl_fh_counter: tl_page, tl_article oder tl_news.
	 *
	 * Einziger Unterschied zwischen den drei Ableitungen.
	 *
	 * @return string
	 */
	abstract protected function quelle(): string;

	/**
	 * Baut die komplette Statistikansicht.
	 *
	 * Contao ruft diese Methode über den key-Eintrag in $GLOBALS['BE_MOD'] auf,
	 * sobald in der Adresszeile &key=counter steht.
	 *
	 * @return string Fertiges HTML des Backend-Templates
	 */
	public function Statistik(): string
	{
		$ebene = (string) Input::get('ebene');

		if (!isset(self::EBENEN[$ebene]))
		{
			$ebene = 'tag';
		}

		$zeitpunkt = $this->zeitpunkt();
		$jahr = (int) date('Y', $zeitpunkt);
		$monat = (int) date('n', $zeitpunkt);
		$tag = (int) date('j', $zeitpunkt);

		$ergebnis = $this->auswerten($ebene, $jahr, $monat, $tag);

		$template = new BackendTemplate('be_counter_statistik');

		$template->bezeichnung = Inhalte::eigenschaft($this->quelle(), 'name');
		$template->spalteZusatz = Inhalte::eigenschaft($this->quelle(), 'zusatz');
		$template->anzahl = Inhalte::anzahl($this->quelle());
		$template->zeilen = $ergebnis['zeilen'];
		$template->gesamt = $ergebnis['gesamt'];
		$template->hatDaten = $ergebnis['gesamt'] > 0;
		$template->cacheDatum = $ergebnis['cacheDatum'];
		$template->diagrammTitel = $this->diagrammTitel($ebene);
		$template->diagramm = Diagramm::balken(
			$ergebnis['verlauf'],
			$this->diagrammTitel($ebene),
			Diagramm::FARBE,
			240,
			'monat' !== $ebene
		);
		$template->zeitraum = $this->zeitraumText($ebene, $jahr, $monat, $tag);
		$template->ebene = $ebene;

		// Navigation fertig aufbereiten, damit das Template keine Adressen baut
		$template->ebenenLinks = $this->ebenenLinks($ebene, $zeitpunkt);
		$template->urlZurueck = $this->url($ebene, $this->verschieben($ebene, $zeitpunkt, -1));
		$template->urlVor = $this->url($ebene, $this->verschieben($ebene, $zeitpunkt, 1));
		$template->urlHeute = $this->url($ebene, time());
		$template->kannVor = $this->verschieben($ebene, $zeitpunkt, 1) <= time();
		$template->urlZurueckModul = 'contao?do='.Inhalte::eigenschaft($this->quelle(), 'modul').'&amp;rt='.self::requestToken();

		$this->versand($template, $ebene, $zeitpunkt, $ergebnis);

		return $template->parse();
	}

	/**
	 * Bereitet den Versand der angezeigten Statistik von Hand vor.
	 *
	 * Ablauf in drei Schritten: Der Knopf „Per E-Mail versenden“ ruft dieselbe
	 * Ansicht mit &versenden=1 auf; dort erscheint ein Formular mit den
	 * eingestellten Empfängern, die für diesen einen Versand noch geändert
	 * werden können; nach dem Absenden geht die Mail hinaus und es erscheint
	 * eine Rückmeldung.
	 *
	 * Verschickt wird genau das, was auf dem Bildschirm steht — die
	 * Auswertung ist bereits errechnet und liegt im Zwischenspeicher. Damit
	 * dauert der Versand von Hand nur Sekunden und läuft nicht in ein
	 * PHP-Zeitlimit, anders als der nächtliche Cronjob, der alles neu rechnet.
	 *
	 * Die im Formular geänderten Adressen gelten nur für diesen Versand; die
	 * dauerhaften Empfänger stehen unter System -> Einstellungen.
	 *
	 * @param BackendTemplate $template  Template der Ansicht
	 * @param string          $ebene     jahr, monat oder tag
	 * @param int             $zeitpunkt Angezeigter Zeitpunkt
	 * @param array           $ergebnis  Bereits errechnete Auswertung
	 *
	 * @phpstan-param Ergebnis $ergebnis
	 *
	 * @return void Setzt versandUrl, versandFormular, versandMeldung und
	 *              versandFehler am Template
	 */
	protected function versand(BackendTemplate $template, string $ebene, int $zeitpunkt, array $ergebnis): void
	{
		$template->versandUrl = $this->url($ebene, $zeitpunkt).'&amp;versenden=1';
		// Abbrechen führt zurück auf denselben Zeitraum, nicht auf heute
		$template->versandAbbruchUrl = $this->url($ebene, $zeitpunkt);
		$template->versandFormular = null;
		$template->versandMeldung = '';
		$template->versandFehler = '';
		$template->requestToken = self::requestToken();

		// Ohne Daten gibt es nichts zu verschicken — dann auch keinen Knopf
		if (empty($ergebnis['zeilen']))
		{
			$template->versandUrl = '';

			return;
		}

		$quelle = $this->quelle();
		$zeitraum = $template->zeitraum;

		if ('counter_versand' === Input::post('FORM_SUBMIT'))
		{
			// postRaw statt post: In „Name <adresse@example.org>“ würden die
			// spitzen Klammern sonst zu Entities und die Adresse unbrauchbar
			$an = Statistikmail::adressen((string) Input::postRaw('empfaenger'));
			$kopie = Statistikmail::adressen((string) Input::postRaw('kopie'));

			$fehler = Statistikmail::versendeErgebnis(
				$quelle,
				$zeitraum,
				$ergebnis,
				Inhalte::anzahl($quelle),
				$an,
				$kopie
			);

			if ('' === $fehler)
			{
				// Adressen maskieren: „Name <a@b.c>“ zerlegte sonst das Markup
				// der Meldung, weil die spitzen Klammern als Tag gelesen würden
				$template->versandMeldung = 'Die Statistik „'.StringUtil::specialchars($zeitraum).'“ wurde an '
					.StringUtil::specialchars(implode(', ', $an)).' verschickt.'
					.($kopie ? ' Kopie an '.StringUtil::specialchars(implode(', ', $kopie)).'.' : '');

				return;
			}

			$template->versandFehler = $fehler;

			// Bei einem Fehlschlag das Formular mit den Eingaben erneut zeigen
			$template->versandFormular = [
				'empfaenger' => (string) Input::postRaw('empfaenger'),
				'kopie'      => (string) Input::postRaw('kopie'),
				'zeitraum'   => $zeitraum,
			];

			return;
		}

		if (Input::get('versenden'))
		{
			$template->versandFormular = [
				'empfaenger' => implode("\n", Statistikmail::eingestellteAdressen($quelle)),
				'kopie'      => implode("\n", Statistikmail::eingestellteAdressen($quelle, true)),
				'zeitraum'   => $zeitraum,
			];
		}
	}

	/**
	 * Ermittelt den anzuzeigenden Zeitpunkt aus der Adresszeile.
	 *
	 * Ohne Parameter wird der heutige Tag gezeigt. Ein Datum in der Zukunft
	 * wird auf heute zurückgesetzt — dort kann es keine Zählwerte geben.
	 *
	 * @return int Zeitstempel innerhalb des gewünschten Zeitraums
	 */
	protected function zeitpunkt(): int
	{
		$datum = (string) Input::get('datum');

		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum))
		{
			return time();
		}

		$zeitpunkt = strtotime($datum);

		if (false === $zeitpunkt || $zeitpunkt > time())
		{
			return time();
		}

		return $zeitpunkt;
	}

	/**
	 * Verschiebt den Zeitpunkt um einen Schritt der aktuellen Ebene.
	 *
	 * @param string $ebene     jahr, monat oder tag
	 * @param int    $zeitpunkt Ausgangszeitpunkt
	 * @param int    $richtung  -1 zurück, +1 vor
	 *
	 * @return int Neuer Zeitstempel
	 */
	protected function verschieben(string $ebene, int $zeitpunkt, int $richtung): int
	{
		$vorzeichen = $richtung > 0 ? '+' : '-';

		// Vor dem Monats- oder Jahressprung auf den Ersten setzen: sonst
		// landet der 31. März einen Monat zurück im März statt im Februar
		if ('monat' === $ebene)
		{
			$anfang = (int) mktime(0, 0, 0, (int) date('n', $zeitpunkt), 1, (int) date('Y', $zeitpunkt));

			return (int) strtotime($vorzeichen.'1 month', $anfang);
		}

		if ('jahr' === $ebene)
		{
			$anfang = (int) mktime(0, 0, 0, 1, 1, (int) date('Y', $zeitpunkt));

			return (int) strtotime($vorzeichen.'1 year', $anfang);
		}

		return (int) strtotime($vorzeichen.'1 day', $zeitpunkt);
	}

	/**
	 * Wertet die Zählertabelle für den gewünschten Zeitraum aus.
	 *
	 * Das Ergebnis landet im Zwischenspeicher, weil das Entpacken tausender
	 * Zählerarrays sonst bei jedem Seitenaufruf anfiele. Der laufende Zeitraum
	 * wird nur eine Stunde vorgehalten, abgeschlossene Zeiträume einen Tag —
	 * an denen ändert sich nichts mehr.
	 *
	 * Zwischengespeichert wird über Symfonys Dateispeicher, den beide
	 * Contao-Fassungen mitbringen. Der Adapter wird unmittelbar erzeugt statt
	 * über den Container geholt, weil die Cache-Dienste dort nicht öffentlich
	 * sind. Der Zeitstempel wandert im Eintrag mit, damit die Ansicht sagen
	 * kann, wie alt die Zahlen sind.
	 *
	 * @param string $ebene jahr, monat oder tag
	 * @param int    $jahr  Jahr, vierstellig
	 * @param int    $monat Monat 1-12
	 * @param int    $tag   Tag 1-31
	 *
	 * @return array Schlüssel zeilen, gesamt, verlauf und cacheDatum
	 *
	 * @phpstan-return AuswertungMitStand
	 */
	protected function auswerten(string $ebene, int $jahr, int $monat, int $tag): array
	{
		$cache = self::speicher();
		$schluessel = implode('.', [$this->quelle(), $ebene, $jahr, $monat, $tag, Inhalte::anzahl($this->quelle())]);

		if (null !== $cache)
		{
			$eintrag = $cache->getItem($schluessel);

			if ($eintrag->isHit())
			{
				$gespeichert = $eintrag->get();

				if (\is_array($gespeichert) && isset($gespeichert['daten'], $gespeichert['zeit']))
				{
					$ergebnis = $gespeichert['daten'];
					$ergebnis['cacheDatum'] = date('d.m.Y H:i', (int) $gespeichert['zeit']);

					return $ergebnis;
				}
			}
		}

		$ergebnis = Bestenliste::auswerten(
			$this->quelle(),
			$this->pfade($ebene, $jahr, $monat, $tag),
			Inhalte::anzahl($this->quelle()),
			$this->achse($ebene, $jahr, $monat, $tag),
			$this->beginn($ebene, $jahr, $monat, $tag)
		);

		if (null !== $cache)
		{
			$eintrag = $cache->getItem($schluessel);
			$eintrag->set(['zeit' => time(), 'daten' => $ergebnis]);
			$eintrag->expiresAfter($this->istLaufend($ebene, $jahr, $monat, $tag) ? 3600 : 86400);
			$cache->save($eintrag);
		}

		$ergebnis['cacheDatum'] = 'gerade eben';

		return $ergebnis;
	}

	/**
	 * Liefert den Zwischenspeicher der Statistiken.
	 *
	 * Die Dateien liegen unterhalb des Contao-Cacheverzeichnisses. Sie gehen
	 * beim Leeren des Contao-Caches verloren — das ist gewollt: Die Auswertung
	 * ist jederzeit neu errechenbar, und nach einer Aktualisierung sollen
	 * ohnehin frische Zahlen erscheinen.
	 *
	 * @return FilesystemAdapter|null Der Speicher oder null, wenn kein
	 *                                Cacheverzeichnis bekannt ist. Ohne
	 *                                Speicher rechnet die Ansicht jedes Mal neu
	 */
	protected static function speicher(): ?FilesystemAdapter
	{
		$container = System::getContainer();

		if (null === $container || !$container->hasParameter('kernel.cache_dir'))
		{
			return null;
		}

		$verzeichnis = $container->getParameter('kernel.cache_dir');

		// getParameter() liefert laut Schnittstelle auch Arrays und Zahlen.
		// Eine blinde Umwandlung nach string wäre bei einem Array ein Fehler
		if (!\is_string($verzeichnis) || '' === $verzeichnis)
		{
			return null;
		}

		return new FilesystemAdapter('counter_statistik', 0, $verzeichnis);
	}

	/**
	 * Beschreibt den ausgewerteten Zeitraum als Pfad ins Zählerarray.
	 *
	 * @param string $ebene jahr, monat oder tag
	 * @param int    $jahr  Jahr, vierstellig
	 * @param int    $monat Monat 1-12
	 * @param int    $tag   Tag 1-31
	 *
	 * @return array Liste mit genau einem Pfad
	 *
	 * @phpstan-return list<Pfad>
	 */
	protected function pfade(string $ebene, int $jahr, int $monat, int $tag): array
	{
		if ('jahr' === $ebene)
		{
			return [[$jahr, 'all']];
		}

		if ('monat' === $ebene)
		{
			return [[$jahr, $monat, 'all']];
		}

		return [[$jahr, $monat, $tag, 'all']];
	}

	/**
	 * Liefert den Zeitstempel, an dem der ausgewertete Zeitraum beginnt.
	 *
	 * Helper\Bestenliste überspringt damit alle Zähler, die seither nicht
	 * mehr angefasst wurden — die können im Zeitraum nichts gezählt haben.
	 *
	 * @param string $ebene jahr, monat oder tag
	 * @param int    $jahr  Jahr, vierstellig
	 * @param int    $monat Monat 1-12
	 * @param int    $tag   Tag 1-31
	 *
	 * @return int Zeitstempel des ersten Augenblicks im Zeitraum
	 */
	protected function beginn(string $ebene, int $jahr, int $monat, int $tag): int
	{
		if ('jahr' === $ebene)
		{
			return (int) mktime(0, 0, 0, 1, 1, $jahr);
		}

		if ('monat' === $ebene)
		{
			return (int) mktime(0, 0, 0, $monat, 1, $jahr);
		}

		return (int) mktime(0, 0, 0, $monat, $tag, $jahr);
	}

	/**
	 * Baut die Achse des Verlaufsdiagramms.
	 *
	 * Alle Punkte werden vorab angelegt, damit Zeitpunkte ohne einen einzigen
	 * Zugriff als Lücke im Diagramm erscheinen statt zu fehlen.
	 *
	 * @param string $ebene jahr, monat oder tag
	 * @param int    $jahr  Jahr, vierstellig
	 * @param int    $monat Monat 1-12
	 * @param int    $tag   Tag 1-31
	 *
	 * @return array Liste aus ['titel' => Beschriftung, 'pfad' => Pfad]
	 *
	 * @phpstan-return list<Achsenpunkt>
	 */
	protected function achse(string $ebene, int $jahr, int $monat, int $tag): array
	{
		$achse = [];

		if ('jahr' === $ebene)
		{
			foreach (range(1, 12) as $m)
			{
				$achse[] = ['titel' => self::MONATE_KURZ[$m], 'pfad' => [$jahr, $m, 'all']];
			}

			return $achse;
		}

		if ('monat' === $ebene)
		{
			$tage = (int) date('t', (int) mktime(0, 0, 0, $monat, 1, $jahr));

			foreach (range(1, $tage) as $t)
			{
				$achse[] = ['titel' => $t.'.', 'pfad' => [$jahr, $monat, $t, 'all']];
			}

			return $achse;
		}

		foreach (range(0, 23) as $stunde)
		{
			// Die Stundenwerte hängen ohne Zwischenebene direkt am Tag; der
			// Schlüssel 'all' daneben ist die Tagessumme
			$achse[] = ['titel' => $stunde.' Uhr', 'pfad' => [$jahr, $monat, $tag, $stunde]];
		}

		return $achse;
	}

	/**
	 * Überschrift des Diagramms.
	 *
	 * @param string $ebene jahr, monat oder tag
	 *
	 * @return string
	 */
	protected function diagrammTitel(string $ebene): string
	{
		if ('jahr' === $ebene)
		{
			return 'Zugriffe je Monat';
		}

		if ('monat' === $ebene)
		{
			return 'Zugriffe je Tag';
		}

		return 'Zugriffe je Stunde';
	}

	/**
	 * Beschreibt den angezeigten Zeitraum im Klartext.
	 *
	 * @param string $ebene jahr, monat oder tag
	 * @param int    $jahr  Jahr, vierstellig
	 * @param int    $monat Monat 1-12
	 * @param int    $tag   Tag 1-31
	 *
	 * @return string etwa „31.07.2026“, „Juli 2026“ oder „2026“
	 */
	protected function zeitraumText(string $ebene, int $jahr, int $monat, int $tag): string
	{
		if ('jahr' === $ebene)
		{
			return (string) $jahr;
		}

		if ('monat' === $ebene)
		{
			return self::MONATE[$monat].' '.$jahr;
		}

		return sprintf('%02d.%02d.%d', $tag, $monat, $jahr);
	}

	/**
	 * Prüft, ob der angezeigte Zeitraum noch läuft.
	 *
	 * @param string $ebene jahr, monat oder tag
	 * @param int    $jahr  Jahr, vierstellig
	 * @param int    $monat Monat 1-12
	 * @param int    $tag   Tag 1-31
	 *
	 * @return bool true, wenn der Zeitraum den heutigen Tag enthält
	 */
	protected function istLaufend(string $ebene, int $jahr, int $monat, int $tag): bool
	{
		if ((int) date('Y') !== $jahr)
		{
			return false;
		}

		if ('jahr' === $ebene)
		{
			return true;
		}

		if ((int) date('n') !== $monat)
		{
			return false;
		}

		return 'monat' === $ebene || (int) date('j') === $tag;
	}

	/**
	 * Baut die drei Ebenen-Knöpfe der Kopfzeile.
	 *
	 * @param string $aktuell   Gerade gewählte Ebene
	 * @param int    $zeitpunkt Angezeigter Zeitpunkt, bleibt beim Wechsel erhalten
	 *
	 * @return array Liste aus text, url und aktiv
	 *
	 * @phpstan-return list<array{text: string, url: string, aktiv: bool}>
	 */
	protected function ebenenLinks(string $aktuell, int $zeitpunkt): array
	{
		$links = [];

		foreach (self::EBENEN as $ebene => $text)
		{
			$links[] = [
				'text'  => 'nach '.$text,
				'url'   => $this->url($ebene, $zeitpunkt),
				'aktiv' => $ebene === $aktuell,
			];
		}

		return $links;
	}

	/**
	 * Baut eine Adresse für die Statistikansicht.
	 *
	 * @param string $ebene     jahr, monat oder tag
	 * @param int    $zeitpunkt Anzuzeigender Zeitpunkt
	 *
	 * @return string Adresse mit maskierten Trennzeichen, direkt für das
	 *                href-Attribut im Template verwendbar
	 */
	protected function url(string $ebene, int $zeitpunkt): string
	{
		return 'contao?do='.Inhalte::eigenschaft($this->quelle(), 'modul')
			.'&amp;key=counter'
			.'&amp;ebene='.$ebene
			.'&amp;datum='.date('Y-m-d', $zeitpunkt)
			.'&amp;rt='.self::requestToken();
	}

	/**
	 * Liefert das Sicherheitsmerkmal (Request-Token) des Backends.
	 *
	 * Die frühere Konstante REQUEST_TOKEN gibt es in Contao 5 nicht mehr, und
	 * der Token-Manager kennt in Contao 4.13 und 5 unterschiedliche Methoden —
	 * daher die Fallunterscheidung.
	 *
	 * @return string Token oder leerer String, wenn keiner zu haben ist.
	 *                Letzteres tritt außerhalb einer Webanfrage auf: der
	 *                Tokenspeicher wird erst beim Bearbeiten eines Requests
	 *                gefüllt und wirft davor eine Ausnahme. Für die Ansicht
	 *                ist das folgenlos — ohne Request gibt es auch keine Links
	 *                zum Anklicken
	 */
	public static function requestToken(): string
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('contao.csrf.token_manager'))
		{
			return '';
		}

		$manager = $container->get('contao.csrf.token_manager');

		if (!$manager instanceof CsrfTokenManagerInterface)
		{
			return '';
		}

		try
		{
			// Contao 4.13 und 5 unterscheiden sich hier: Der ContaoCsrfTokenManager
			// kennt getDefaultTokenValue(), die Symfony-Schnittstelle nur getToken()
			if ($manager instanceof ContaoCsrfTokenManager)
			{
				return $manager->getDefaultTokenValue();
			}

			return $manager->getToken((string) Config::get('csrfTokenName'))->getValue();
		}
		catch (\Throwable $e)
		{
			return '';
		}
	}
}
