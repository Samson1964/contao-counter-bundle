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
 * Backend-Statistik der Artikelzugriffe.
 *
 * Erreichbar über Artikel -> Statistik (contao?do=article&key=counter).
 *
 * Zu jeder Seite gehört mindestens ein Artikel; weitere können frei angelegt
 * werden. Getrennt gezählt wird ein Artikel nur, wenn er eigens aufgerufen
 * wird — in der Adresse am Bestandteil „articles“ zu erkennen. Ein Artikel,
 * der einfach als Inhalt seiner Seite mitläuft, steckt in der Seitenstatistik.
 */
class StatistikArticles extends Statistik
{
	/**
	 * Name der Quelltabelle in tl_fh_counter.
	 *
	 * @return string Immer tl_article
	 */
	protected function quelle(): string
	{
		return 'tl_article';
	}
}
