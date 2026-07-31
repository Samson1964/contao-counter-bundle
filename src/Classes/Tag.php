<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Classes;

use Contao\FrontendTemplate;
use Schachbulle\ContaoCounterBundle\Helper\Auswertung;
use Schachbulle\ContaoCounterBundle\Helper\Zaehlwerk;

/**
 * Insert-Tags des Zählers.
 *
 * Wer den Zähler nicht als Frontend-Modul einbinden will, kann stattdessen
 * zwei Insert-Tags verwenden:
 *
 *   {{fhcounter}}      zählt den Aufruf und gibt nichts aus
 *   {{fhcounterview}}  gibt die Zahlen über das Template fhcounter_mini aus
 *
 * Die Insert-Tags kennen keine Moduleinstellungen und arbeiten deshalb mit
 * festen Vorgaben (siehe Konstanten). Wer die Werte anpassen will, nimmt die
 * Frontend-Module.
 */
class Tag
{
	/**
	 * Sekunden, die ein Besucher beim Insert-Tag als online gilt
	 */
	private const ONLINEZEIT = 180;

	/**
	 * Sekunden bis zur erneuten Zählung desselben Besuchers
	 */
	private const SPERRZEIT = 600;

	/**
	 * Template der Ausgabe
	 */
	private const TEMPLATE = 'fhcounter_mini';

	/**
	 * Behandelt das Insert-Tag {{fhcounter}} und zählt den Aufruf.
	 *
	 * Backend-Benutzer werden nicht mitgezählt — beim Insert-Tag gibt es keine
	 * Einstellung dafür, und ein mitgezählter Redakteur verfälscht die Zahlen
	 * mehr, als ein fehlender Redakteur sie verkürzt.
	 *
	 * @param string $strTag Vollständiges Insert-Tag ohne geschweifte Klammern
	 *
	 * @return string|false Leerer String, wenn dieses Tag gemeint war
	 *                      (das Tag hinterlässt also keine Ausgabe im Text),
	 *                      sonst false, damit Contao die übrigen Hooks fragt
	 */
	public function fhcounter(string $strTag)
	{
		$arrSplit = explode('::', $strTag);

		if ('fhcounter' !== $arrSplit[0] && 'cache_fhcounter' !== $arrSplit[0])
		{
			return false;
		}

		$zaehlwerk = new Zaehlwerk(self::ONLINEZEIT, self::SPERRZEIT, false);
		$zaehlwerk->zaehleAufruf(true, true, true);

		return '';
	}

	/**
	 * Behandelt das Insert-Tag {{fhcounterview}} und gibt die Zahlen aus.
	 *
	 * Voraussetzung ist, dass zuvor gezählt wurde — entweder über
	 * {{fhcounter}} oder über das Zählermodul. Ohne Zählung stehen keine Werte
	 * in $GLOBALS['fhcounter'] und das Template gibt entsprechend nichts aus.
	 *
	 * @param string $strTag Vollständiges Insert-Tag ohne geschweifte Klammern
	 *
	 * @return string|false Fertiges HTML oder false, wenn ein anderes Tag gemeint war
	 */
	public function fhcounter_view(string $strTag)
	{
		$arrSplit = explode('::', $strTag);

		if ('fhcounterview' !== $arrSplit[0] && 'cache_fhcounterview' !== $arrSplit[0])
		{
			return false;
		}

		$template = new FrontendTemplate(self::TEMPLATE);

		$auswertung = new Auswertung([
			'pages'        => true,
			'articles'     => true,
			'news'         => true,
			'infos'        => true,
			'diagramme'    => true,
			'trennzeichen' => true,
		]);

		$auswertung->fuelleTemplate($template);

		return $template->parse();
	}
}
