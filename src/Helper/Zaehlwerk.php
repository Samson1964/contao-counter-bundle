<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

use Contao\ArticleModel;
use Contao\Config;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\Database;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Das eigentliche Zählwerk.
 *
 * Diese Klasse trägt die gesamte Zähllogik, die früher wortgleich im
 * Frontend-Modul (Classes\Register) und in der Insert-Tag-Klasse
 * (Classes\Tag) stand. Beide rufen jetzt hierher, damit eine Änderung nicht
 * mehr an zwei Stellen nachgezogen werden muss.
 *
 * Gezählt wird je Inhaltstyp (Seite, Artikel, Nachricht) ein Datensatz in
 * tl_fh_counter. Die Zählstände liegen dort als serialisiertes, nach
 * Jahr/Monat/Tag/Stunde geschachteltes Array. Nebenbei werden die zuletzt
 * gesehenen IP-Adressen mitgeführt: einmal für die Sperrzeit (wer innerhalb
 * dieser Zeit wiederkommt, wird nicht erneut gezählt) und einmal für die
 * Anzeige „Besucher online“.
 */
final class Zaehlwerk
{
	/**
	 * Name der Zählertabelle
	 */
	public const TABELLE = 'tl_fh_counter';

	/**
	 * Sekunden, die ein Besucher nach seinem Aufruf als „online“ gilt
	 */
	private int $onlinezeit;

	/**
	 * Sekunden, die vergehen müssen, bis derselbe Besucher erneut zählt
	 */
	private int $sperrzeit;

	/**
	 * Aufrufzeitpunkt. Wird einmal festgehalten, damit alle Zähler eines
	 * Seitenaufrufs denselben Zeitstempel bekommen — sonst könnte ein Aufruf
	 * kurz vor Mitternacht auf zwei Tage verteilt landen
	 */
	private int $zeit;

	/**
	 * Zerlegter Aufrufzeitpunkt, so wie ihn das Zählerarray verschachtelt:
	 * Jahr vierstellig, Monat/Tag/Stunde ohne führende Null.
	 *
	 * Bewusst Zeichenketten: date() liefert sie so, und als Array-Schlüssel
	 * wandelt PHP sie ohnehin in Zahlen um. Eine Umwandlung hier würde die
	 * vorhandenen Zählstände nicht verändern, aber unnötig Arbeit machen.
	 */
	private string $jahr;
	private string $monat;
	private string $tag;
	private string $stunde;

	/**
	 * IP-Adresse des aktuellen Besuchers
	 */
	private string $ip;

	/**
	 * Ist gerade ein Backend-Benutzer im Frontend unterwegs?
	 */
	private bool $beBenutzer = false;

	/**
	 * Zwischenspeicher der Weiterleitungsseiten aller Nachrichtenarchive.
	 * Statisch, weil sich das innerhalb eines Seitenaufrufs nicht ändert und
	 * die Abfrage sonst je Zähler erneut liefe.
	 *
	 * @var list<int>|null
	 */
	private static ?array $nachrichtenleser = null;

	/**
	 * Richtet das Zählwerk für einen Seitenaufruf ein.
	 *
	 * @param int  $onlinezeit       Sekunden, die ein Besucher als online gilt.
	 *                               Werte unter 1 werden auf 120 gehoben, sonst
	 *                               wäre die Online-Liste immer sofort leer
	 * @param int  $sperrzeit        Sekunden bis zur erneuten Zählung desselben
	 *                               Besuchers. Werte unter 1 werden auf 900
	 *                               gehoben, sonst zählte jeder Reload mit
	 * @param bool $beBenutzerZaehlen Wenn false, wird bei angemeldetem
	 *                               Backend-Benutzer nicht gezählt
	 */
	public function __construct(int $onlinezeit = 120, int $sperrzeit = 900, bool $beBenutzerZaehlen = true)
	{
		$this->onlinezeit = $onlinezeit > 0 ? $onlinezeit : 120;
		$this->sperrzeit = $sperrzeit > 0 ? $sperrzeit : 900;

		$this->zeit = time();
		$this->jahr = date('Y', $this->zeit);
		$this->monat = date('n', $this->zeit);
		$this->tag = date('j', $this->zeit);
		$this->stunde = date('G', $this->zeit);

		$this->ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

		if (!$beBenutzerZaehlen)
		{
			$this->beBenutzer = self::istBackendBenutzer();
		}
	}

	/**
	 * Zählt den aktuellen Seitenaufruf für alle gewünschten Inhaltstypen.
	 *
	 * Das ist der übliche Einstieg: Die Methode ermittelt selbst, welche
	 * Seite, welcher Artikel und welche Nachricht gerade angezeigt werden,
	 * und stößt für jeden Treffer die Zählung an. Ein abgeschalteter Typ
	 * verursacht keine einzige Datenbankabfrage.
	 *
	 * Ist ein Backend-Benutzer angemeldet und soll nicht mitgezählt werden,
	 * unterbleibt die Zählung vollständig.
	 *
	 * @param bool $seiten       Seitenaufrufe zählen (tl_page)
	 * @param bool $artikel      Artikelaufrufe zählen (tl_article)
	 * @param bool $nachrichten  Nachrichtenaufrufe zählen (tl_news)
	 *
	 * @return void Ergebnis der Zählung steht anschließend in
	 *              $GLOBALS['fhcounter'] für die Ausgabemodule bereit
	 */
	public function zaehleAufruf(bool $seiten, bool $artikel, bool $nachrichten): void
	{
		if ($this->beBenutzer)
		{
			return;
		}

		$objPage = self::aktuelleSeite();

		if (null === $objPage)
		{
			return;
		}

		if ($seiten)
		{
			$this->registriere((int) $objPage->id, 'tl_page');
		}

		if ($artikel)
		{
			$id = self::aktuellerArtikel();

			if ($id > 0)
			{
				$this->registriere($id, 'tl_article');
			}
		}

		if ($nachrichten)
		{
			$id = self::aktuelleNachricht($objPage);

			if ($id > 0)
			{
				$this->registriere($id, 'tl_news');
			}
		}
	}

	/**
	 * Schreibt einen Aufruf in die Zählertabelle und füllt $GLOBALS['fhcounter'].
	 *
	 * Der Ablauf in Stichworten: Datensatz laden, Online- und Sperrliste um
	 * abgelaufene Einträge bereinigen, prüfen ob dieser Besucher überhaupt
	 * zählt, gegebenenfalls die Zählstände hochsetzen und alles zurück in die
	 * Datenbank schreiben.
	 *
	 * Zählt der Besucher nicht (Sperrzeit noch nicht abgelaufen), wird nur die
	 * Online-Liste aktualisiert — das serialisierte Zählerarray bleibt dann
	 * unangetastet und muss nicht geschrieben werden.
	 *
	 * @param int    $id     ID des Inhalts in seiner Quelltabelle. 0 oder
	 *                       negative Werte werden protokolliert und ignoriert
	 * @param string $quelle Name der Quelltabelle: tl_page, tl_article oder tl_news
	 *
	 * @return void Seiteneffekte: schreibt in tl_fh_counter und füllt
	 *              $GLOBALS['fhcounter'][$quelle] sowie ['default']
	 */
	public function registriere(int $id, string $quelle): void
	{
		if ($id < 1)
		{
			// Ohne ID lässt sich nichts zuordnen. Das ist meist harmlos (etwa
			// eine Nachricht, deren Alias nicht mehr existiert), kann aber auf
			// eine kaputte URL hindeuten — deshalb protokollierbar
			if (!Config::get('counter_donotlogid'))
			{
				Protokoll::fehler(
					'Counter: source_id='.$id.', source_name='.$quelle.', URI='.($_SERVER['REQUEST_URI'] ?? ''),
					__METHOD__
				);
			}

			return;
		}

		$db = Database::getInstance();

		// Gezielt nur die benötigten Spalten holen. Ein SELECT * zöge hier die
		// serialisierten Blöcke doppelt heran und bringt nichts
		$datensatz = $db->prepare(
			'SELECT id, tstamp, starttime, lastcounting, lastip, toponline, iparray, counter, online
			   FROM '.self::TABELLE.'
			  WHERE source=? AND pid=?
			  ORDER BY id'
		)->execute($quelle, $id);

		$vorhanden = $datensatz->numRows > 0;

		if ($vorhanden)
		{
			$toponline = StringUtil::deserialize($datensatz->toponline, true);
			$iparray = StringUtil::deserialize($datensatz->iparray, true);
			$counter = StringUtil::deserialize($datensatz->counter, true);
			$online = StringUtil::deserialize($datensatz->online, true);
			$letzterBesuch = (int) $datensatz->tstamp;
			$letzteZaehlung = (int) $datensatz->lastcounting;
			$startzeit = (int) $datensatz->starttime;
			$letzteIp = (string) $datensatz->lastip;

			// Aus früheren Fassungen können doppelte Datensätze stammen. Der
			// erste gewinnt, die übrigen fliegen raus — der neue Verbundindex
			// über source und pid verhindert, dass wieder welche entstehen
			if ($datensatz->numRows > 1)
			{
				while ($datensatz->next())
				{
					$db->prepare('DELETE FROM '.self::TABELLE.' WHERE id=?')->execute($datensatz->id);
				}
			}
		}
		else
		{
			$toponline = [];
			$iparray = [];
			$counter = [];
			$online = [];
			$letzterBesuch = 0;
			$letzteZaehlung = 0;
			$startzeit = 0;
			$letzteIp = '';
		}

		// Besucher entfernen, deren Onlinezeit abgelaufen ist, und den
		// aktuellen eintragen
		$onlineEnde = $this->zeit - $this->onlinezeit;

		foreach ($online as $adresse => $zeitpunkt)
		{
			if ($zeitpunkt < $onlineEnde)
			{
				unset($online[$adresse]);
			}
		}

		$online[$this->ip] = $this->zeit;

		// Bestmarke der gleichzeitigen Besucher fortschreiben
		if (!isset($toponline['count']))
		{
			$toponline = ['count' => 0, 'time' => 0, 'onlinetime' => $this->onlinezeit];
		}

		if (\count($online) > $toponline['count'])
		{
			$toponline['count'] = \count($online);
			$toponline['time'] = $this->zeit;
			$toponline['onlinetime'] = $this->onlinezeit;
		}

		// Sperrliste bereinigen und prüfen, ob dieser Besucher schon gezählt wurde
		$sperrEnde = $this->zeit - $this->sperrzeit;

		foreach ($iparray as $adresse => $zeitpunkt)
		{
			if ($zeitpunkt < $sperrEnde)
			{
				unset($iparray[$adresse]);
			}
		}

		$zaehlen = !isset($iparray[$this->ip]);
		$iparray[$this->ip] = $this->zeit;

		if ($zaehlen)
		{
			$counter = $this->erhoehe($counter);

			$werte = [
				'tstamp'       => $this->zeit,
				'totalhits'    => $counter['all'],
				'lastip'       => $this->ip,
				'lastcounting' => $this->zeit,
				'toponline'    => serialize($toponline),
				'iparray'      => serialize($iparray),
				'counter'      => serialize($counter),
				'online'       => serialize($online),
			];

			$letzterBesuch = $this->zeit;
			$letzteZaehlung = $this->zeit;
			$letzteIp = $this->ip;

			if ($vorhanden)
			{
				$db->prepare('UPDATE '.self::TABELLE.' %s WHERE source=? AND pid=?')
				   ->set($werte)
				   ->execute($quelle, $id);
			}
			else
			{
				$werte['starttime'] = $this->zeit;
				$werte['source'] = $quelle;
				$werte['pid'] = $id;
				$startzeit = $this->zeit;

				$db->prepare('INSERT INTO '.self::TABELLE.' %s')->set($werte)->execute();
			}
		}
		elseif ($vorhanden)
		{
			// Wiederkehrer innerhalb der Sperrzeit: nur Online-Liste und
			// Zeitstempel auffrischen. Das große Zählerarray bleibt außen vor,
			// damit nicht bei jedem Reload ein mediumtext-Feld neu geschrieben wird
			$db->prepare('UPDATE '.self::TABELLE.' %s WHERE source=? AND pid=?')
			   ->set([
					'tstamp'    => $this->zeit,
					'lastip'    => $this->ip,
					'toponline' => serialize($toponline),
					'iparray'   => serialize($iparray),
					'online'    => serialize($online),
			   ])
			   ->execute($quelle, $id);

			$letzterBesuch = $this->zeit;
			$letzteIp = $this->ip;
		}

		$daten = [
			'counting'     => $zaehlen,
			'tstamp'       => $letzterBesuch,
			'starttime'    => $startzeit,
			'source'       => $quelle,
			'pid'          => $id,
			'totalhits'    => $counter['all'] ?? 0,
			'lastcounting' => $letzteZaehlung,
			'lastip'       => $letzteIp,
			'toponline'    => $toponline,
			'counter'      => $counter,
			'online'       => \count($online),
		];

		$GLOBALS['fhcounter'][$quelle] = $daten;

		// Der Standardzähler trägt immer die zuletzt gezählte Quelle. Weil die
		// Reihenfolge Seite -> Artikel -> Nachricht lautet, gewinnt damit der
		// speziellste Inhalt, der auf dieser Seite angezeigt wird
		$GLOBALS['fhcounter']['default'] = $daten;
	}

	/**
	 * Erhöht alle Ebenen des Zählerarrays um eins.
	 *
	 * Fehlende Ebenen werden vorher mit 0 angelegt. Genau das ging in
	 * früheren Fassungen schief: Tages- und Stundenwert wurden beim ersten
	 * Aufruf auf 0 gesetzt statt auf 1, wodurch je Tag ein Zugriff verloren
	 * ging und Tages- und Monatssumme nicht mehr zusammenpassten.
	 *
	 * @param array $counter Bisheriges Zählerarray, notfalls leer
	 *
	 * @phpstan-param array<int|string, mixed> $counter
	 *
	 * @return array Das erhöhte Zählerarray, immer mit Schlüssel „all“
	 *
	 * @phpstan-return array<int|string, mixed>
	 */
	private function erhoehe(array $counter): array
	{
		$counter['all'] = ($counter['all'] ?? 0) + 1;
		$counter[$this->jahr]['all'] = ($counter[$this->jahr]['all'] ?? 0) + 1;
		$counter[$this->jahr][$this->monat]['all'] = ($counter[$this->jahr][$this->monat]['all'] ?? 0) + 1;
		$counter[$this->jahr][$this->monat][$this->tag]['all'] = ($counter[$this->jahr][$this->monat][$this->tag]['all'] ?? 0) + 1;
		$counter[$this->jahr][$this->monat][$this->tag][$this->stunde] = ($counter[$this->jahr][$this->monat][$this->tag][$this->stunde] ?? 0) + 1;

		return $counter;
	}

	/**
	 * Protokolliert einen 404-Aufruf, sofern gewünscht.
	 *
	 * Contao selbst vermerkt nicht, welche Adresse ins Leere lief. Für die
	 * Pflege einer Website ist genau das aber die nützlichste Information,
	 * deshalb schreibt der Zähler sie mitsamt IP und Browserkennung ins
	 * Systemprotokoll. Abschaltbar über die Einstellung counter_donotlog404,
	 * weil ein von Bots abgegraster Auftritt das Protokoll sonst zumüllt.
	 *
	 * @param PageModel|null $objPage Aktuelle Seite. Ohne Seite passiert nichts
	 *
	 * @return void
	 */
	public static function protokolliere404(?PageModel $objPage): void
	{
		if (null === $objPage || 'error_404' !== $objPage->type)
		{
			return;
		}

		$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

		if ('' === $uri || Config::get('counter_donotlog404'))
		{
			return;
		}

		Protokoll::fehler(
			'Fehler 404: '.$uri
			.' --- IP='.($_SERVER['REMOTE_ADDR'] ?? '')
			.' --- AGENT='.($_SERVER['HTTP_USER_AGENT'] ?? ''),
			__METHOD__
		);
	}

	/**
	 * Liefert die gerade angezeigte Seite.
	 *
	 * Bevorzugt wird das PageModel aus dem Request — so macht es Contao 5
	 * selbst. Die globale Variable $objPage dient nur noch als Rückfallebene
	 * für ältere Aufrufwege.
	 *
	 * @return PageModel|null Die Seite oder null, wenn gerade keine
	 *                        Frontend-Seite aufgebaut wird
	 */
	public static function aktuelleSeite(): ?PageModel
	{
		$stack = self::dienst('request_stack', RequestStack::class);

		if (null !== $stack)
		{
			$request = $stack->getCurrentRequest();

			if (null !== $request)
			{
				$seite = $request->attributes->get('pageModel');

				if ($seite instanceof PageModel)
				{
					return $seite;
				}
			}
		}

		// Rückfallebene für ältere Aufrufwege. Die Prüfung auf den Typ ist
		// kein Misstrauen gegen Contao, sondern gegen Fremdcode, der die
		// globale Variable überschreibt — das kommt vor
		return ($GLOBALS['objPage'] ?? null) instanceof PageModel ? $GLOBALS['objPage'] : null;
	}

	/**
	 * Holt einen Dienst aus dem Container und prüft seinen Typ.
	 *
	 * Contaos Container liefert `object|null`; wer darauf ohne Prüfung eine
	 * Methode aufruft, bekommt im Zweifel einen Fatal Error. Diese Methode
	 * liefert den Dienst nur, wenn er wirklich die erwartete Klasse hat, und
	 * sonst null — der Aufrufer entscheidet dann, wie er ohne weitermacht.
	 *
	 * @param string $name    Dienstname im Container
	 * @param string $klasse  Erwartete Klasse oder Schnittstelle
	 *
	 * @return object|null Der Dienst oder null, wenn es ihn nicht gibt oder er
	 *                     einen anderen Typ hat
	 *
	 * @phpstan-template T of object
	 * @phpstan-param class-string<T> $klasse
	 * @phpstan-return T|null
	 */
	private static function dienst(string $name, string $klasse): ?object
	{
		$container = System::getContainer();

		if (null === $container || !$container->has($name))
		{
			return null;
		}

		$dienst = $container->get($name);

		return $dienst instanceof $klasse ? $dienst : null;
	}

	/**
	 * Ermittelt den gerade angezeigten Artikel.
	 *
	 * Contao stellt die Kennung des Artikels als URL-Parameter „articles“
	 * bereit. Fehlt der Parameter, wird gar nicht erst in die Datenbank
	 * gegriffen — früher lief hier bei jedem Seitenaufruf eine überflüssige
	 * Abfrage.
	 *
	 * @return int ID des Artikels oder 0, wenn keiner angezeigt wird
	 */
	private static function aktuellerArtikel(): int
	{
		$kennung = Input::get('articles');

		if (empty($kennung))
		{
			return 0;
		}

		$artikel = ArticleModel::findByIdOrAlias($kennung);

		return null !== $artikel ? (int) $artikel->id : 0;
	}

	/**
	 * Ermittelt die gerade angezeigte Nachricht.
	 *
	 * Contao verrät einem Modul nicht, welche Nachricht der Nachrichtenleser
	 * gerade zeigt. Der Zähler behilft sich: Er sammelt die
	 * Weiterleitungsseiten aller Nachrichtenarchive und schneidet, wenn die
	 * aktuelle Seite eine davon ist, den Alias aus der Adresse heraus.
	 *
	 * Der Weg über die Adresse ist fehleranfällig (er setzt voraus, dass der
	 * Alias unmittelbar hinter dem Seitenalias steht), war aber schon immer so
	 * und bleibt es hier — eine Umstellung würde die vorhandenen Zählstände
	 * auf schachbund.de gefährden.
	 *
	 * @param PageModel $objPage Aktuell aufgebaute Seite
	 *
	 * @return int ID der Nachricht oder 0, wenn keine angezeigt wird
	 */
	private static function aktuelleNachricht(PageModel $objPage): int
	{
		if (!\in_array((int) $objPage->id, self::nachrichtenleser(), true))
		{
			return 0;
		}

		// Das Adress-Suffix hängt in Contao 4.13+ an der Seite, nicht mehr
		// global an den Einstellungen
		$suffix = (string) ($objPage->urlSuffix ?? Config::get('urlSuffix'));
		$uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

		if ('' === $suffix || substr($uri, -\strlen($suffix)) !== $suffix)
		{
			return 0;
		}

		$alias = substr($uri, \strlen((string) $objPage->alias) + 2, -\strlen($suffix));

		if ('' === $alias)
		{
			return 0;
		}

		$nachricht = Database::getInstance()
			->prepare('SELECT id FROM tl_news WHERE alias=?')
			->limit(1)
			->execute($alias);

		return $nachricht->numRows ? (int) $nachricht->id : 0;
	}

	/**
	 * Liefert die Seiten-IDs aller Nachrichtenleser.
	 *
	 * Das Ergebnis wird für die Dauer des Seitenaufrufs festgehalten, weil
	 * sich die Archive zwischendurch nicht ändern.
	 *
	 * @return array Liste von Seiten-IDs, gegebenenfalls leer
	 *
	 * @phpstan-return list<int>
	 */
	private static function nachrichtenleser(): array
	{
		if (null !== self::$nachrichtenleser)
		{
			return self::$nachrichtenleser;
		}

		self::$nachrichtenleser = [];

		$archive = Database::getInstance()
			->prepare('SELECT DISTINCT jumpTo FROM tl_news_archive WHERE jumpTo>0')
			->execute();

		while ($archive->next())
		{
			self::$nachrichtenleser[] = (int) $archive->jumpTo;
		}

		return self::$nachrichtenleser;
	}

	/**
	 * Prüft, ob im Frontend ein Backend-Benutzer angemeldet ist.
	 *
	 * Der frühere Weg über BackendUser::getInstance() funktioniert unter
	 * Contao 5 nicht mehr zuverlässig; der TokenChecker des Kerns beantwortet
	 * dieselbe Frage in beiden Versionen sauber.
	 *
	 * @return bool true, wenn ein Backend-Benutzer angemeldet ist. Ohne
	 *              nutzbaren TokenChecker wird false angenommen — im Zweifel
	 *              lieber mitzählen als eine Seite gar nicht zählen
	 */
	public static function istBackendBenutzer(): bool
	{
		$pruefer = self::dienst('contao.security.token_checker', TokenChecker::class);

		return null !== $pruefer && $pruefer->hasBackendUser();
	}
}
