<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Modules;

/**
 * Backend-Statistik der Seitenzugriffe.
 *
 * Erreichbar über Seitenstruktur -> Statistik (contao?do=page&key=counter).
 */
class StatistikPages extends Statistik
{
	/**
	 * Name der Quelltabelle in tl_fh_counter.
	 *
	 * @return string Immer tl_page
	 */
	protected function quelle(): string
	{
		return 'tl_page';
	}
}
