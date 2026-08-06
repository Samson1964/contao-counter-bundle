<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Cron;

use Contao\Config;
use Contao\Email;
use Contao\FrontendTemplate;
use Schachbulle\ContaoCounterBundle\Helper\Bestenliste;
use Schachbulle\ContaoCounterBundle\Helper\Inhalte;
use Schachbulle\ContaoCounterBundle\Helper\Protokoll;

/**
 * Täglicher Cronjob: verschickt die Zugriffsstatistik per E-Mail.
 *
 * Ersetzt die drei Skripte pagescount.php, articlescount.php und
 * newscount.php, die früher außerhalb der Erweiterung in web/php lagen und
 * vom Hoster als Cronjob aufgerufen wurden. Diese Skripte banden Contao über
 * system/initialize.php ein — einen Weg, den es seit Contao 4.5 nicht mehr
 * gibt. Sie liefen also nur noch, weil auf schachbund.de eine alte Umgebung
 * daneben stand.
 *
 * Der Cronjob läuft täglich und verschickt:
 *   - immer      die Bestenliste des Vortags
 *   - montags    zusätzlich die der vergangenen Woche (Montag bis Sonntag)
 *   - am Ersten  zusätzlich die des Vormonats
 *
 * Je Inhaltsart geht eine eigene E-Mail an einen eigenen Verteiler: Wer die
 * Seitenstatistik bekommt, ist selten dieselbe Runde wie beim
 * Nachrichten-Ranking. Aufbau, Absender und Betreffzusatz gelten dagegen für
 * alle drei gemeinsam.
 *
 * Alles steht unter System -> Einstellungen im Bereich „Zähler“. Eine
 * Inhaltsart ohne Empfänger wird übersprungen; ohne jeden Empfänger bricht der
 * Cronjob still ab.
 *
 * @phpstan-import-type Pfad from Bestenliste
 * @phpstan-import-type Zeile from Bestenliste
 * @phpstan-import-type Ergebnis from Bestenliste
 *
 * @phpstan-type Zeitraum array{bezeichnung: string, pfade: list<Pfad>, seit: int}
 * @phpstan-type GefaerbteZeile array{platz: int, hits: int, id: int, titel: string, zusatz: string, tstamp: int, datum: string, css: string, farbe: string}
 */
final class Statistikmail
{
	/**
	 * Farbskala für das Alter eines Inhalts, von taufrisch bis uralt.
	 *
	 * Schlüssel ist das Mindestalter in Tagen; die Liste wird von oben nach
	 * unten geprüft, der erste passende Eintrag gewinnt. Die Farben sind aus
	 * den alten Skripten übernommen, damit die Mails vertraut aussehen.
	 */
	private const ALTERSFARBEN = [
		999999 => '#C0C0C0',
		365    => '#C7A896',
		180    => '#FF7D5E',
		120    => '#FFB9A8',
		60     => '#FFD5CA',
		30     => '#C99D05',
		20     => '#B1BA14',
		14     => '#E1EA39',
		11     => '#F1F5A0',
		7      => '#91FF91',
		3      => '#00F400',
		0      => '#00F400',
	];

	/**
	 * Volle Monatsnamen für die Betreffzeile
	 */
	private const MONATE = [
		1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni',
		7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
	];

	/**
	 * Einstiegspunkt des Cronjobs.
	 *
	 * Contao ruft die Methode einmal täglich auf (Dienst-Kennzeichnung
	 * contao.cronjob mit interval daily in services.yml). Sie ermittelt, welche
	 * Zeiträume heute fällig sind, und verschickt je Zeitraum und Inhaltsart
	 * eine E-Mail.
	 *
	 * @return void Seiteneffekt: verschickt E-Mails und vermerkt Fehlschläge
	 *              im Systemprotokoll. Wirft bewusst keine Ausnahme — ein
	 *              abgebrochener Versand darf die übrigen Cronjobs nicht
	 *              mitreißen
	 */
	public function __invoke(): void
	{
		if (!Config::get('counter_mail'))
		{
			return;
		}

		// Empfänger je Inhaltsart einsammeln. Eine Inhaltsart ohne Empfänger
		// fällt heraus — damit ist zugleich gesagt, welche Statistiken
		// überhaupt verschickt werden
		$verteiler = [];

		foreach (Inhalte::QUELLEN as $quelle)
		{
			$an = self::adressen(self::einstellung($quelle, 'counter_mail_empfaenger_'));

			if ($an)
			{
				$verteiler[$quelle] = [
					'an'    => $an,
					'kopie' => self::adressen(self::einstellung($quelle, 'counter_mail_kopie_')),
				];
			}
		}

		if (!$verteiler)
		{
			return;
		}

		// Läuft der Cronjob über die Weboberfläche, gilt das PHP-Zeitlimit des
		// Webservers. Die Antwort ist zu diesem Zeitpunkt längst hinausgegangen
		// (Contao stößt die Cronjobs beim kernel.terminate an), es wartet also
		// niemand. Wo der Hoster es zulässt, nehmen wir uns deshalb die Zeit.
		if (\function_exists('set_time_limit'))
		{
			@set_time_limit(0);
		}

		$begonnen = microtime(true);
		$bericht = [];

		foreach ($this->zeitraeume() as $zeitraum)
		{
			foreach ($verteiler as $quelle => $adressen)
			{
				$dauer = microtime(true);
				$verschickt = $this->versende($quelle, $zeitraum, $adressen['an'], $adressen['kopie']);

				$bericht[] = sprintf(
					'%s %s: %s (%01.1f s)',
					Inhalte::eigenschaft($quelle, 'name'),
					$zeitraum['bezeichnung'],
					$verschickt,
					microtime(true) - $dauer
				);
			}
		}

		// Contao selbst schreibt für einen durchgelaufenen Cronjob nichts ins
		// Systemprotokoll. Ohne diese Meldung wäre nicht zu unterscheiden, ob
		// eine fehlende E-Mail an leeren Zahlen lag oder daran, dass der Lauf
		// vorzeitig abgebrochen wurde
		Protokoll::cron(
			sprintf('Counter: Statistik-Mails abgearbeitet in %01.1f s — ', microtime(true) - $begonnen)
			.implode('; ', $bericht),
			__METHOD__
		);
	}

	/**
	 * Liest eine Einstellung, deren Name auf die Inhaltsart endet.
	 *
	 * Die Endung ist der Bezeichner des Backend-Moduls (page, article, news),
	 * damit die Namen der Einstellungen ohne zweite Zuordnungstabelle
	 * auskommen — sie stehen bereits in Helper\Inhalte.
	 *
	 * @param string $quelle  Tabellenname: tl_page, tl_article oder tl_news
	 * @param string $praefix Namensanfang der Einstellung, mit Unterstrich am Ende
	 *
	 * @return string Wert der Einstellung, leerer String wenn nicht gepflegt
	 */
	private static function einstellung(string $quelle, string $praefix): string
	{
		return (string) Config::get($praefix.Inhalte::eigenschaft($quelle, 'modul'));
	}

	/**
	 * Bestimmt die heute fälligen Zeiträume.
	 *
	 * Der Vortag ist immer dabei. Montags kommt die abgelaufene Woche hinzu,
	 * am Monatsersten der abgelaufene Monat. Beides wird bewusst erst nach
	 * Ablauf des Zeitraums verschickt, damit die Zahlen vollständig sind.
	 *
	 * **Reihenfolge: das Seltenste zuerst.** Jeder Zeitraum kostet einen
	 * Durchlauf durch die gesamte Zählertabelle. Läuft der Cronjob über eine
	 * Weboberfläche, kann ihn ein PHP-Zeitlimit mittendrin abschneiden. Dann
	 * soll die Tagesstatistik ausfallen — die kommt morgen wieder — und nicht
	 * die Monatsstatistik, die dann für immer fehlt. Genau das ist am
	 * 01.08.2026 passiert.
	 *
	 * @return array Liste aus ['bezeichnung' => Text für den Betreff,
	 *               'pfade' => Pfade ins Zählerarray], Monat vor Woche vor Tag
	 *
	 * @phpstan-return list<Zeitraum>
	 */
	private function zeitraeume(): array
	{
		$jetzt = time();
		$zeitraeume = [];

		// Am Monatsersten: der abgelaufene Monat
		if ('1' === date('j', $jetzt))
		{
			$vormonat = strtotime('-1 month', (int) mktime(0, 0, 0, (int) date('n', $jetzt), 1, (int) date('Y', $jetzt)));

			$zeitraeume[] = [
				'bezeichnung' => self::MONATE[(int) date('n', $vormonat)].' '.date('Y', $vormonat),
				'pfade'       => [[(int) date('Y', $vormonat), (int) date('n', $vormonat), 'all']],
				'seit'        => (int) mktime(0, 0, 0, (int) date('n', $vormonat), 1, (int) date('Y', $vormonat)),
			];
		}

		// Montags: die vergangene Woche von Montag bis Sonntag
		if ('1' === date('N', $jetzt))
		{
			$montag = strtotime('-7 days', $jetzt);
			$pfade = [];

			for ($i = 0; $i < 7; ++$i)
			{
				$pfade[] = self::tagespfad(strtotime('+'.$i.' days', $montag));
			}

			$zeitraeume[] = [
				'bezeichnung' => date('d.m.Y', $montag).' bis '.date('d.m.Y', strtotime('+6 days', $montag)),
				'pfade'       => $pfade,
				'seit'        => (int) strtotime('midnight', $montag),
			];
		}

		// Vortag — steht bewusst zuletzt
		$gestern = strtotime('-1 day', $jetzt);
		$zeitraeume[] = [
			'bezeichnung' => date('d.m.Y', $gestern),
			'pfade'       => [self::tagespfad($gestern)],
			'seit'        => (int) strtotime('midnight', $gestern),
		];

		return $zeitraeume;
	}

	/**
	 * Baut den Pfad eines einzelnen Tages ins Zählerarray.
	 *
	 * @param int $zeitpunkt Beliebiger Zeitstempel innerhalb des Tages
	 *
	 * @return array Pfad in der Form [Jahr, Monat, Tag, 'all']
	 *
	 * @phpstan-return Pfad
	 */
	private static function tagespfad(int $zeitpunkt): array
	{
		return [(int) date('Y', $zeitpunkt), (int) date('n', $zeitpunkt), (int) date('j', $zeitpunkt), 'all'];
	}

	/**
	 * Stellt eine E-Mail zusammen und verschickt sie.
	 *
	 * Enthält der Zeitraum keinen einzigen Zugriff, wird nichts verschickt —
	 * eine leere Tabelle im Postfach hilft niemandem.
	 *
	 * @param string $quelle     Tabellenname: tl_page, tl_article oder tl_news
	 * @param array  $zeitraum   Eintrag aus zeitraeume()
	 * @param array  $empfaenger Empfängeradressen dieser Inhaltsart, nie leer
	 * @param array  $kopie      Adressen, die eine Kopie erhalten; darf leer sein
	 *
	 * @phpstan-param Zeitraum     $zeitraum
	 * @phpstan-param list<string> $empfaenger
	 * @phpstan-param list<string> $kopie
	 *
	 * @return string Kurzbericht für das Systemprotokoll: entweder die Zahl der
	 *                Empfänger oder der Grund, weshalb nichts hinausging
	 */
	private function versende(string $quelle, array $zeitraum, array $empfaenger, array $kopie = []): string
	{
		$anzahl = (int) (Config::get('counter_mail_anzahl') ?: 50);
		$ergebnis = Bestenliste::auswerten($quelle, $zeitraum['pfade'], $anzahl, [], $zeitraum['seit']);

		$fehler = self::versendeErgebnis($quelle, $zeitraum['bezeichnung'], $ergebnis, $anzahl, $empfaenger, $kopie);

		if ('' !== $fehler)
		{
			return $fehler;
		}

		return sprintf('an %d Empfänger', \count($empfaenger) + \count($kopie));
	}

	/**
	 * Verschickt eine bereits ausgewertete Bestenliste.
	 *
	 * Von hier aus gehen sowohl die Mails des Cronjobs hinaus als auch die,
	 * die im Backend von Hand ausgelöst werden. Der Unterschied liegt nur
	 * darin, woher die Auswertung stammt: Der Cronjob rechnet sie frisch aus,
	 * das Backend reicht die bereits angezeigte (und zwischengespeicherte)
	 * Auswertung durch — deshalb dauert der Versand von Hand nur Sekunden und
	 * läuft nicht in ein Zeitlimit.
	 *
	 * Enthält die Auswertung keine Zeile, wird nichts verschickt — eine leere
	 * Tabelle im Postfach hilft niemandem.
	 *
	 * @param string $quelle     Tabellenname: tl_page, tl_article oder tl_news
	 * @param string $zeitraum   Beschreibung des Zeitraums für Betreff und Text,
	 *                           etwa „Juli 2026“ oder „31.07.2026“
	 * @param array  $ergebnis   Auswertung aus Helper\Bestenliste (zeilen, gesamt)
	 * @param int    $anzahl     Zahl der Listenplätze, nur für die Überschrift
	 * @param array  $empfaenger Empfängeradressen; eine leere Liste bricht ab
	 * @param array  $kopie      Adressen, die eine Kopie erhalten; darf leer sein
	 *
	 * @phpstan-param Ergebnis     $ergebnis
	 * @phpstan-param list<string> $empfaenger
	 * @phpstan-param list<string> $kopie
	 *
	 * @return string Leerer String bei Erfolg, sonst der Grund des Fehlschlags
	 *                im Klartext. Fehler werden zusätzlich protokolliert und
	 *                bewusst nicht geworfen: Ein nicht erreichbarer Mailserver
	 *                darf weder den Cronjob abbrechen noch das Backend zerlegen
	 */
	public static function versendeErgebnis(string $quelle, string $zeitraum, array $ergebnis, int $anzahl, array $empfaenger, array $kopie = []): string
	{
		if (!$empfaenger)
		{
			return 'Es ist kein Empfänger angegeben.';
		}

		if (empty($ergebnis['zeilen']))
		{
			return 'Für diesen Zeitraum sind keine Zugriffe gezählt, es gibt nichts zu verschicken.';
		}

		$bezeichnung = Inhalte::eigenschaft($quelle, 'name');
		$titel = 'Top-'.$anzahl.' '.$bezeichnung.' '.$zeitraum;

		$template = new FrontendTemplate((string) (Config::get('counter_mail_template') ?: 'counter_mail_standard'));

		$template->titel = $titel;
		$template->bezeichnung = $bezeichnung;
		$template->einzahl = Inhalte::eigenschaft($quelle, 'einzahl');
		$template->spalteZusatz = Inhalte::eigenschaft($quelle, 'zusatz');
		$template->zeitraum = $zeitraum;
		$template->gesamt = $ergebnis['gesamt'];
		$template->zeilen = self::faerbe($ergebnis['zeilen']);
		$template->istArtikel = ('tl_article' === $quelle);

		// Der gesamte Mailaufbau steht in der Fehlerbehandlung, nicht nur das
		// Absenden: Schon sendCc() prüft die Adressen und wirft bei einer
		// unbrauchbaren eine Ausnahme. Stand der Aufruf davor, riss ein
		// einziger fehlerhafter Kopieempfänger den ganzen Cronjob mit — auch
		// die Mails, die danach noch hinausgegangen wären (04.08.2026).
		try
		{
			$mail = new Email();
			$mail->from = (string) (Config::get('counter_mail_absender') ?: Config::get('adminEmail'));
			$mail->fromName = self::text((string) (Config::get('counter_mail_absendername') ?: 'Webstatistik'));
			$mail->subject = trim(self::text((string) Config::get('counter_mail_betreff')).' '.$titel);
			$mail->html = $template->parse();

			if ($kopie)
			{
				$mail->sendCc(...$kopie);
			}

			$mail->sendTo(...$empfaenger);
		}
		catch (\Throwable $e)
		{
			Protokoll::fehler('Counter: Statistik-Mail "'.$titel.'" konnte nicht verschickt werden: '.$e->getMessage(), __METHOD__);

			return 'Der Versand ist gescheitert: '.$e->getMessage();
		}

		return '';
	}

	/**
	 * Macht aus einem in tl_settings gespeicherten Text wieder Klartext.
	 *
	 * Contao legt Eingaben mit HTML-Entities ab (siehe adressen()). Für
	 * Absendername und Betreffzusatz fällt das weniger auf als bei den
	 * Adressen, sähe im Postfach aber genauso falsch aus — „Schach &amp; Co.“
	 * statt „Schach & Co.“.
	 *
	 * @param string $wert Rohwert aus den Einstellungen
	 *
	 * @return string Klartext ohne HTML-Entities
	 */
	private static function text(string $wert): string
	{
		return html_entity_decode($wert, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}

	/**
	 * Liefert die eingestellten Empfänger einer Inhaltsart.
	 *
	 * @param string $quelle Tabellenname: tl_page, tl_article oder tl_news
	 * @param bool   $kopie  true liefert die Kopieempfänger statt der Empfänger
	 *
	 * @return array Liste der Adressen, leer wenn nichts gepflegt ist
	 *
	 * @phpstan-return list<string>
	 */
	public static function eingestellteAdressen(string $quelle, bool $kopie = false): array
	{
		return self::adressen(self::einstellung($quelle, $kopie ? 'counter_mail_kopie_' : 'counter_mail_empfaenger_'));
	}

	/**
	 * Zerlegt eine Adresseingabe in einzelne Empfänger.
	 *
	 * Erlaubt sind Kommas und Zeilenumbrüche als Trennzeichen sowie die von
	 * Contao unterstützte Schreibweise „Name <adresse@example.org>“.
	 *
	 * **Zwei Vorkehrungen, die beide aus einem Ausfall am 04.08.2026 stammen:**
	 *
	 * 1. Die Werte werden von HTML-Entities befreit. Contaos Input::post()
	 *    ruft stripTags() auf — und zwar unabhängig von der Einstellung
	 *    decodeEntities. Die spitze Klammer vor der Adresse sieht für
	 *    stripTags wie ein unbekanntes Tag aus und wird zu „&lt;“, während
	 *    die schließende Klammer stehen bleibt. Gespeichert steht dann
	 *    „Name &lt;adresse@example.org>“ — für Symfony keine gültige Adresse.
	 * 2. Offensichtlich unbrauchbare Adressen fliegen heraus, statt den
	 *    Versand scheitern zu lassen. Eine falsch geschriebene Adresse darf
	 *    nicht dazu führen, dass die übrigen Empfänger nichts bekommen.
	 *
	 * Öffentlich, weil das Backend die im Versandformular eingetippten
	 * Adressen mit denselben Regeln zerlegen muss wie die eingestellten.
	 *
	 * @param string $eingabe Rohwert aus den Einstellungen oder dem Formular
	 *
	 * @return array Liste der brauchbaren Adressen, leer wenn nichts
	 *               Verwertbares drinsteht. Aussortierte Einträge werden im
	 *               Systemprotokoll vermerkt
	 *
	 * @phpstan-return list<string>
	 */
	public static function adressen(string $eingabe): array
	{
		$teile = preg_split('/[,\r\n]+/', $eingabe) ?: [];
		$adressen = [];

		foreach ($teile as $teil)
		{
			$teil = trim(html_entity_decode($teil, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

			if ('' === $teil)
			{
				continue;
			}

			if (!self::istAdresse($teil))
			{
				Protokoll::fehler('Counter: „'.$teil.'“ ist keine brauchbare E-Mail-Adresse und wird beim Versand übergangen.', __METHOD__);

				continue;
			}

			$adressen[] = $teil;
		}

		return $adressen;
	}

	/**
	 * Prüft, ob ein Eintrag als Empfängeradresse taugt.
	 *
	 * Akzeptiert die blanke Adresse ebenso wie die Schreibweise mit Namen
	 * davor, also „adresse@example.org“ und „Name <adresse@example.org>“ —
	 * beides versteht Contaos Email-Klasse.
	 *
	 * @param string $eintrag Bereits entschlüsselter und getrimmter Eintrag
	 *
	 * @return bool true, wenn der enthaltene Adressteil eine gültige
	 *              E-Mail-Adresse ist
	 */
	private static function istAdresse(string $eintrag): bool
	{
		// Name davor? Dann nur den Teil in den spitzen Klammern prüfen
		if (preg_match('/^.*<\s*([^<>]+)\s*>$/', $eintrag, $treffer))
		{
			$eintrag = trim($treffer[1]);
		}

		return false !== filter_var($eintrag, FILTER_VALIDATE_EMAIL);
	}

	/**
	 * Ergänzt jede Zeile um eine Farbe, die das Alter des Inhalts anzeigt.
	 *
	 * Von Grün (ganz frisch) über Gelb bis Rot und Grau (älter als ein Jahr).
	 * In E-Mails muss die Farbe als Attribut an der Zeile stehen, weil viele
	 * Programme Stilangaben im Kopfbereich verwerfen.
	 *
	 * @param array $zeilen Zeilen aus Helper\Bestenliste
	 *
	 * @phpstan-param list<Zeile> $zeilen
	 *
	 * @return array Dieselben Zeilen, jeweils um den Schlüssel „farbe“ ergänzt
	 *
	 * @phpstan-return list<GefaerbteZeile>
	 */
	private static function faerbe(array $zeilen): array
	{
		$jetzt = time();

		foreach ($zeilen as $i => $zeile)
		{
			$tage = $zeile['tstamp'] ? (int) (($jetzt - $zeile['tstamp']) / 86400) : 999999;
			$farbe = '#FFFFFF';

			foreach (self::ALTERSFARBEN as $grenze => $wert)
			{
				if ($tage >= $grenze)
				{
					$farbe = $wert;
					break;
				}
			}

			$zeilen[$i]['farbe'] = $farbe;
		}

		return $zeilen;
	}

}
