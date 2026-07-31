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
use Contao\StringUtil;
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
 * Alle Adressen und die Zahl der Listenplätze stehen unter
 * System -> Einstellungen im Bereich „Zähler“. Ohne Empfänger passiert
 * nichts — der Cronjob bricht dann still ab.
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

		$empfaenger = self::adressen((string) Config::get('counter_mail_empfaenger'));

		if (!$empfaenger)
		{
			return;
		}

		$quellen = StringUtil::deserialize(Config::get('counter_mail_quellen'), true);
		$quellen = array_values(array_intersect($quellen, Inhalte::QUELLEN));

		if (!$quellen)
		{
			return;
		}

		foreach ($this->zeitraeume() as $zeitraum)
		{
			foreach ($quellen as $quelle)
			{
				$this->versende($quelle, $zeitraum, $empfaenger);
			}
		}
	}

	/**
	 * Bestimmt die heute fälligen Zeiträume.
	 *
	 * Der Vortag ist immer dabei. Montags kommt die abgelaufene Woche hinzu,
	 * am Monatsersten der abgelaufene Monat. Beides wird bewusst erst nach
	 * Ablauf des Zeitraums verschickt, damit die Zahlen vollständig sind.
	 *
	 * @return array Liste aus ['bezeichnung' => Text für den Betreff,
	 *               'pfade' => Pfade ins Zählerarray]
	 */
	private function zeitraeume(): array
	{
		$jetzt = time();
		$zeitraeume = [];

		// Vortag
		$gestern = strtotime('-1 day', $jetzt);
		$zeitraeume[] = [
			'bezeichnung' => date('d.m.Y', $gestern),
			'pfade'       => [self::tagespfad($gestern)],
		];

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
			];
		}

		// Am Monatsersten: der abgelaufene Monat
		if ('1' === date('j', $jetzt))
		{
			$vormonat = strtotime('-1 month', (int) mktime(0, 0, 0, (int) date('n', $jetzt), 1, (int) date('Y', $jetzt)));

			$zeitraeume[] = [
				'bezeichnung' => self::MONATE[(int) date('n', $vormonat)].' '.date('Y', $vormonat),
				'pfade'       => [[(int) date('Y', $vormonat), (int) date('n', $vormonat), 'all']],
			];
		}

		return $zeitraeume;
	}

	/**
	 * Baut den Pfad eines einzelnen Tages ins Zählerarray.
	 *
	 * @param int $zeitpunkt Beliebiger Zeitstempel innerhalb des Tages
	 *
	 * @return array Pfad in der Form [Jahr, Monat, Tag, 'all']
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
	 * @param array  $empfaenger Empfängeradressen
	 *
	 * @return void
	 */
	private function versende(string $quelle, array $zeitraum, array $empfaenger): void
	{
		$anzahl = (int) (Config::get('counter_mail_anzahl') ?: 50);
		$ergebnis = Bestenliste::auswerten($quelle, $zeitraum['pfade'], $anzahl);

		if (!$ergebnis['zeilen'])
		{
			return;
		}

		$bezeichnung = Inhalte::eigenschaft($quelle, 'name');
		$titel = 'Top-'.$anzahl.' '.$bezeichnung.' '.$zeitraum['bezeichnung'];

		$template = new FrontendTemplate((string) (Config::get('counter_mail_template') ?: 'counter_mail_standard'));

		$template->titel = $titel;
		$template->bezeichnung = $bezeichnung;
		$template->einzahl = Inhalte::eigenschaft($quelle, 'einzahl');
		$template->spalteZusatz = Inhalte::eigenschaft($quelle, 'zusatz');
		$template->zeitraum = $zeitraum['bezeichnung'];
		$template->gesamt = $ergebnis['gesamt'];
		$template->zeilen = $this->faerbe($ergebnis['zeilen']);
		$template->istArtikel = ('tl_article' === $quelle);

		$mail = new Email();
		$mail->from = (string) (Config::get('counter_mail_absender') ?: Config::get('adminEmail'));
		$mail->fromName = (string) (Config::get('counter_mail_absendername') ?: 'Webstatistik');
		$mail->subject = trim((string) Config::get('counter_mail_betreff').' '.$titel);
		$mail->html = $template->parse();

		$kopie = self::adressen((string) Config::get('counter_mail_kopie'));

		if ($kopie)
		{
			$mail->sendCc(...$kopie);
		}

		try
		{
			$mail->sendTo(...$empfaenger);
		}
		catch (\Exception $e)
		{
			// Ein nicht erreichbarer Mailserver darf den Cronjob nicht
			// abbrechen — die übrigen Mails sollen trotzdem hinausgehen
			Protokoll::fehler('Counter: Statistik-Mail "'.$titel.'" konnte nicht verschickt werden: '.$e->getMessage(), __METHOD__);
		}
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
	 * @return array Dieselben Zeilen, jeweils um den Schlüssel „farbe“ ergänzt
	 */
	private function faerbe(array $zeilen): array
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

	/**
	 * Zerlegt eine Adresseingabe in einzelne Empfänger.
	 *
	 * Erlaubt sind Kommas und Zeilenumbrüche als Trennzeichen sowie die von
	 * Contao unterstützte Schreibweise „Name <adresse@example.org>“.
	 *
	 * @param string $eingabe Rohwert aus den Einstellungen
	 *
	 * @return array Liste der Adressen, leer wenn nichts Brauchbares drinsteht
	 */
	private static function adressen(string $eingabe): array
	{
		$teile = preg_split('/[,\r\n]+/', $eingabe) ?: [];
		$adressen = [];

		foreach ($teile as $teil)
		{
			$teil = trim($teil);

			if ('' !== $teil)
			{
				$adressen[] = $teil;
			}
		}

		return $adressen;
	}
}
