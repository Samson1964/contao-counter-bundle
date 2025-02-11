<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Schachbulle\ContaoCounterBundle\Modules;

class StatistikPages
{

	/**
	 * Funktion Statistik
	 */
	public function Statistik()
	{

		$Template = new \BackendTemplate('be_counter_pages');
		$Template->request = ampersand(\Environment::getInstance()->request, true);

		$nachrichtenarchiv = self::Archive(); // Nachrichten-Archive laden
		$caching = true; // Cache einschalten
		$cachetime = 3600 * 24 * 365; // 1 Jahr (Standard)

		// Aktuelles Datum ermitteln
		$aktJahr  = date('Y');
		$aktMonat = date('n');
		$aktTag   = date('j');

		// Datum und Datumsrichtung aus URL ermitteln
		$urlJahr = (int)\Input::get('jahr');
		$urlMonat = (int)\Input::get('monat');
		$urlTag = (int)\Input::get('tag');
		$differenz = \Input::get('differenz');

		// Neues Datum für anzuzeigende Daten ermitteln
		if(!$urlJahr && !$urlMonat && !$urlTag)
		{
			// Keine Datumsparameter gefunden, deshalb aktuellen Tag vorgeben
			$viewJahr = $aktJahr;
			$viewMonat = $aktMonat;
			$viewTag = $aktTag;
			//$caching = false; // Cache ausschalten, da aktueller Tag gewünscht ist
			$cachetime = 3600; // 1 Stunde
		}
		elseif($urlJahr && !$urlMonat && !$urlTag)
		{
			// Nur Jahr-Parameter gefunden, Monat und Tag auf 0 setzen
			$viewJahr = $urlJahr;
			$viewMonat = 0;
			$viewTag = 0;
			// Anderes Jahr gewünscht?
			if($differenz) $viewJahr += $differenz;
			if($viewJahr == $aktJahr)
			{
				//$caching = false; // Cache abschalten, wenn aktuelles Jahr gewünscht ist
				$cachetime = 3600; // 1 Stunde
			}
		}
		elseif($urlJahr && $urlMonat && !$urlTag)
		{
			// Nur Jahr/Monat-Parameter gefunden, Tag auf 0 setzen
			$viewJahr = $urlJahr;
			$viewMonat = $urlMonat;
			$viewTag = 0;
			// Anderer Monat gewünscht?
			if($differenz)
			{
				$viewMonat += $differenz;
				if($viewMonat == 13)
				{
					$viewMonat = 1;
					$viewJahr++;
				}
				elseif($viewMonat == 0)
				{
					$viewMonat = 12;
					$viewJahr--;
				}
			}
			if($viewJahr == $aktJahr && $viewMonat == $aktMonat)
			{
				//$caching = false; // Cache abschalten, wenn aktuelles Jahr/Monat gewünscht ist
				$cachetime = 3600; // 1 Stunde
			}
		}
		elseif($urlJahr && $urlMonat && $urlTag)
		{
			// Alle Datum-Parameter gefunden
			$viewJahr = $urlJahr;
			$viewMonat = $urlMonat;
			$viewTag = $urlTag;
			// Anderer Tag gewünscht?
			if($differenz)
			{
				$zeitstempel = mktime(0, 0, 0, $viewMonat, $viewTag, $viewJahr);
				$neuzeit = strtotime($differenz." day", $zeitstempel);
				$viewJahr  = date('Y', $neuzeit);
				$viewMonat = date('n', $neuzeit);
				$viewTag   = date('j', $neuzeit);
			}
			if($viewJahr == $aktJahr && $viewMonat == $aktMonat && $viewTag == $aktTag)
			{
				//$caching = false; // Cache abschalten, wenn aktueller Tag gewünscht ist
				$cachetime = 3600; // 1 Stunde
			}
		}

		// Vor- und Zurücklinks generieren
		$vorLink = '<a href="contao?do=page&key=counter&jahr='.$viewJahr.'&monat='.$viewMonat.'&tag='.$viewTag.'&differenz=1&rt='.REQUEST_TOKEN.'"><img src="bundles/contaocounter/images/plus.png"></a>';
		$zurueckLink = '<a href="contao?do=page&key=counter&jahr='.$viewJahr.'&monat='.$viewMonat.'&tag='.$viewTag.'&differenz=-1&rt='.REQUEST_TOKEN.'"><img src="bundles/contaocounter/images/minus.png"></a>';

		// Anzuzeigendes Datum erstellen
		if($viewJahr) $datum = $viewJahr;
		if($viewMonat) $datum = $viewMonat.'.'.$datum;
		if($viewTag) $datum = $viewTag.'.'.$datum;

		// Link-Parameter für aktuelles Jahr
		$aktJahrLink = 'jahr='.$aktJahr.'&monat=0&tag=0';
		// Link-Parameter für aktuellen Monat
		$aktMonatLink = 'jahr='.$aktJahr.'&monat='.$aktMonat.'&tag=0';
		// Link-Parameter für aktuellen Tag
		$aktTagLink = 'jahr='.$aktJahr.'&monat='.$aktMonat.'&tag='.$aktTag;

		// Abfrage und Auswertung starten
		if($caching)
		{
			// Der Cache soll verwendet werden
			// Cache initialisieren
			$cache = new \Schachbulle\ContaoHelperBundle\Classes\Cache(array('name' => 'PagesStatistik', 'extension' => '.cache'));
			$cache->eraseExpired(); // Cache aufräumen, abgelaufene Schlüssel löschen
			$cacheKey = $viewJahr.'.'.$viewMonat.'.'.$viewTag.'.'.$GLOBALS['TL_CONFIG']['counter_topx_pages'];

			// Cache laden
			if($cache->isCached($cacheKey))
			{
				$cacheDatum = $cache->retrieve($cacheKey, true);
				$cacheResult = $cache->retrieve($cacheKey);
			}
		}

		if(isset($cacheResult))
		{
			// Cachedaten zuweisen
			$daten = $cacheResult;
		}
		else
		{
			// Nichts im Cache gefunden, deshalb Datenbank abfragen
			// Zähler für Nachrichten einlesen
			$ergebnis = \Database::getInstance()->prepare("SELECT * FROM tl_fh_counter WHERE source=?")
			                                    ->execute('tl_page');

			// Zähler für Seiten auswerten
			$zaehlerdaten = array();
			if($ergebnis->numRows)
			{
				while($ergebnis->next())
				{
					// Seite laden
					$pages = \Database::getInstance()->prepare("SELECT * FROM tl_page WHERE id=?")
					                                 ->execute($ergebnis->pid);
					$zaehlerdaten[] = array
					(
						'hits'   => self::getCounter($ergebnis->counter, array($viewJahr, $viewMonat, $viewTag)),
						'id'     => $ergebnis->pid,
						'alias'  => $pages->alias,
						'titel'  => $pages->pageTitle ? $pages->pageTitle : $pages->title,
						'datum'  => date("d.m.Y H:i",$pages->date),
						'tstamp' => $pages->date,
					);
				}
			}

			// Zählerdaten sortieren, wenn welche vorhanden sind
			if($zaehlerdaten)
			{
				$sorted = self::sortArrayByFields
				(
					$zaehlerdaten,
					array
					(
						'hits'     => SORT_DESC,
						'tstamp'   => SORT_DESC
					)
				);
			}
			else
			{
				$sorted = $zaehlerdaten; // Keine Zählerdaten vorhanden
			}

			$daten = array_slice($sorted, 0, $GLOBALS['TL_CONFIG']['counter_topx_pages']); // Daten-Array kürzen
			// Daten-Array modifizieren
			$platz = 1;
			$even = false;
			for($x=0; $x<count($daten); $x++)
			{
				$even = $even ? false : true;
				$daten[$x]['platz'] = $platz;
				$daten[$x]['css'] = $even ? 'even' : 'odd';
				$platz++;
			}

			// Cache speichern
			if($caching)
			{
				$cachetime = 3600 * 24 * 365; // 1 Jahr
				$cache->store($cacheKey, $daten, $cachetime);
			}
		}

		$Template->Anzahl = $GLOBALS['TL_CONFIG']['counter_topx_pages'];
		$Template->daten = $daten;
		$Template->Datum = $datum;
		$Template->cacheDatum = (isset($cacheDatum) && $cacheDatum > 0) ? date('d.m.Y H:i', $cacheDatum) : 'gerade eben';
		$Template->VorLink = $vorLink;
		$Template->ZurueckLink = $zurueckLink;
		$Template->LinkAktuellesJahr = '<a href="contao?do=page&key=counter&'.$aktJahrLink.'&rt='.REQUEST_TOKEN.'">'.$aktJahr.'</a>';
		$Template->LinkAktuellerMonat = '<a href="contao?do=page&key=counter&'.$aktMonatLink.'&rt='.REQUEST_TOKEN.'">'.$aktMonat.'.'.$aktJahr.'</a>';
		$Template->LinkAktuellerTag = '<a href="contao?do=page&key=counter&'.$aktTagLink.'&rt='.REQUEST_TOKEN.'">'.$aktTag.'.'.$aktMonat.'.'.$aktJahr.'</a>';

		return $Template->parse();
	}

	/**
	 * Funktion Archive
	 * Liefert ein Array mit der Archiv-ID als Schlüssel und dem Archiv-Zitel als Wert
	 * @return array
	 */
	private function getCounter($data, $datum)
	{
		$zaehlerdaten = unserialize($data);
		if($datum[0] && $datum[1] && $datum[2] && isset($zaehlerdaten[$datum[0]][$datum[1]][$datum[2]]['all']))
		{
			return $zaehlerdaten[$datum[0]][$datum[1]][$datum[2]]['all'];
		}
		elseif($datum[0] && $datum[1] && !$datum[2] && $zaehlerdaten[$datum[0]][$datum[1]]['all'])
		{
			return $zaehlerdaten[$datum[0]][$datum[1]]['all'];
		}
		elseif($datum[0] && !$datum[1] && !$datum[2] && $zaehlerdaten[$datum[0]]['all'])
		{
			return $zaehlerdaten[$datum[0]]['all'];
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Funktion Archive
	 * Liefert ein Array mit der Archiv-ID als Schlüssel und dem Archiv-Titel als Wert
	 * @return array
	 */
	private function Archive()
	{
		// Nachrichtenarchive einlesen
		$ergebnis = \Database::getInstance()->prepare("SELECT * FROM tl_page")
		                                    ->execute();
		$daten = array();
		// Nachrichtenarchiv-Titel den Nachrichtenarchiv-ID's zuordnen
		if($ergebnis->numRows)
		{
			while($ergebnis->next())
			{
				$daten[$ergebnis->id] = $ergebnis->title;
			}
		}
		return $daten;
	}

	function sortArrayByFields($arr, $fields)
	{
		$sortFields = array();
		$args       = array();

		foreach ($arr as $key => $row) {
			foreach ($fields as $field => $order) {
				$sortFields[$field][$key] = $row[$field];
			}
		}

		foreach ($fields as $field => $order) {
			$args[] = $sortFields[$field];

			if (is_array($order)) {
				foreach ($order as $pt) {
					$args[$pt];
				}
			} else {
				$args[] = $order;
			}
		}

		$args[] = &$arr;

		call_user_func_array('array_multisort', $args);

		return $arr;
	}
}
