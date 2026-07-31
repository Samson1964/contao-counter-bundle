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
use Contao\FrontendTemplate;
use Contao\Module;
use Schachbulle\ContaoCounterBundle\Helper\Auswertung;

/**
 * Frontend-Modul „Ausgabemodul“.
 *
 * Gibt die vom Zählermodul ermittelten Werte über ein wählbares Template aus.
 * Das Modul zählt selbst nichts und darf deshalb beliebig oft eingebunden
 * werden — es muss aber im Seitenlayout hinter dem Zählermodul stehen.
 */
class Frontend extends Module
{
	/**
	 * Zeigt im Backend einen Platzhalter statt der Modulausgabe.
	 *
	 * @return string Platzhalter im Backend, sonst die reguläre Modulausgabe
	 */
	public function generate()
	{
		if (Register::istBackend())
		{
			$objTemplate = new BackendTemplate('be_fhcounter');

			$objTemplate->wildcard = '### COUNTER AUSGABEMODUL ###';
			$objTemplate->title = $this->name;
			$objTemplate->id = $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Baut das Ausgabetemplate auf und füllt es mit den Zählerwerten.
	 *
	 * Das Modul verwendet nicht das Standardtemplate der Modulklasse, sondern
	 * das in den Moduleinstellungen gewählte. Deshalb wird $this->Template
	 * hier neu gesetzt, statt nur befüllt.
	 *
	 * @return void
	 */
	protected function compile()
	{
		$this->Template = new FrontendTemplate($this->fhc_template ?: 'fhcounter_standard');

		$auswertung = new Auswertung([
			'pages'        => (bool) $this->fhc_view_pages,
			'articles'     => (bool) $this->fhc_view_articles,
			'news'         => (bool) $this->fhc_view_news,
			'infos'        => (bool) $this->fhc_infos_counter,
			'debug'        => (bool) $this->fhc_infos_debug,
			'diagramme'    => (bool) $this->fhc_view_diagrams,
			'tabellen'     => (bool) $this->fhc_view_tables,
			'trennzeichen' => (bool) $this->fhc_1000_separator,
		]);

		$auswertung->fuelleTemplate($this->Template);
	}
}
