<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Classes;

use Contao\BackendTemplate;
use Contao\Module;
use Contao\System;
use Schachbulle\ContaoCounterBundle\Helper\Zaehlwerk;

/**
 * Frontend-Modul „Zählermodul“.
 *
 * Zählt den Aufruf der aktuellen Seite und — sofern angezeigt — des aktuellen
 * Artikels und der aktuellen Nachricht. Das Modul gibt selbst nichts aus; es
 * muss lediglich im Seitenlayout vor dem Ausgabemodul stehen, weil dieses die
 * Zahlen aus $GLOBALS['fhcounter'] übernimmt.
 *
 * Die eigentliche Arbeit macht Helper\Zaehlwerk. Diese Klasse reicht nur die
 * Moduleinstellungen weiter.
 */
class Register extends Module
{
	/**
	 * Zeigt im Backend einen Platzhalter statt der Modulausgabe.
	 *
	 * Contao ruft generate() auch in der Modulübersicht des Backends auf. Dort
	 * darf natürlich nicht gezählt werden, deshalb der Umweg über den
	 * Geltungsbereich des Requests. Die früher übliche Konstante TL_MODE gibt
	 * es in Contao 5 nicht mehr.
	 *
	 * @return string Platzhalter im Backend, sonst die reguläre Modulausgabe
	 *                (die hier leer ist, weil das Modul nur zählt)
	 */
	public function generate()
	{
		if (self::istBackend())
		{
			$objTemplate = new BackendTemplate('be_fhcounter');

			$objTemplate->wildcard = '### COUNTER ZÄHLERMODUL ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Führt die Zählung durch.
	 *
	 * Zusätzlich wird ein Aufruf der 404-Seite im Systemprotokoll vermerkt,
	 * weil Contao selbst nicht festhält, welche Adresse ins Leere lief.
	 *
	 * @return void Seiteneffekte: schreibt in tl_fh_counter, füllt
	 *              $GLOBALS['fhcounter'] und protokolliert gegebenenfalls
	 */
	protected function compile()
	{
		Zaehlwerk::protokolliere404($GLOBALS['objPage'] ?? null);

		$zaehlwerk = new Zaehlwerk(
			(int) $this->fhc_onlinetime,
			(int) $this->fhc_registernewtime,
			(bool) $this->fhc_register_be_user
		);

		$zaehlwerk->zaehleAufruf(
			(bool) $this->fhc_register_pages,
			(bool) $this->fhc_register_articles,
			(bool) $this->fhc_register_news
		);
	}

	/**
	 * Prüft, ob der aktuelle Request zum Backend gehört.
	 *
	 * Ausgelagert, weil das Ausgabemodul dieselbe Prüfung braucht und der
	 * Weg über den ScopeMatcher deutlich mehr Zeilen kostet als das frühere
	 * TL_MODE == 'BE'.
	 *
	 * @return bool true bei einem Backend-Request, sonst false. Ohne Container
	 *              oder Request wird false angenommen — dann läuft der Code
	 *              außerhalb einer Webanfrage, etwa in einem Test
	 */
	public static function istBackend(): bool
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('request_stack'))
		{
			return false;
		}

		$request = $container->get('request_stack')->getCurrentRequest();

		if (null === $request)
		{
			return false;
		}

		return $container->get('contao.routing.scope_matcher')->isBackendRequest($request);
	}
}
