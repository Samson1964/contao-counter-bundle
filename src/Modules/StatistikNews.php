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
 * Backend-Statistik der Nachrichtenzugriffe.
 *
 * Erreichbar über Nachrichten -> Statistik (contao?do=news&key=counter).
 */
class StatistikNews extends Statistik
{
	/**
	 * Name der Quelltabelle in tl_fh_counter.
	 *
	 * @return string Immer tl_news
	 */
	protected function quelle(): string
	{
		return 'tl_news';
	}
}
