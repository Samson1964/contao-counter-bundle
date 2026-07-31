<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use Schachbulle\ContaoCounterBundle\ContaoCounterBundle;

/**
 * Meldet die Erweiterung beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface
{
	/**
	 * Nennt das Bundle und seine Ladereihenfolge.
	 *
	 * Der Zähler wird nach dem Kern und nach dem Nachrichten-Bundle geladen:
	 * Er ergänzt deren DCA-Dateien um den Aufruf der Statistik, was nur
	 * funktioniert, wenn die Grundfassung bereits steht.
	 *
	 * @param ParserInterface $parser Vom Manager gestellter Parser; wird hier
	 *                                nicht gebraucht, gehört aber zur Schnittstelle
	 *
	 * @return array Liste mit der Bundle-Beschreibung
	 */
	public function getBundles(ParserInterface $parser): array
	{
		return [
			BundleConfig::create(ContaoCounterBundle::class)
				->setLoadAfter([ContaoCoreBundle::class, ContaoNewsBundle::class]),
		];
	}
}
