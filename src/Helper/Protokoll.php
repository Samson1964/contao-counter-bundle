<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\System;
use Psr\Log\LoggerInterface;

/**
 * Schreibt Meldungen ins Contao-Systemprotokoll.
 *
 * Die alte Bequemlichkeitsmethode System::log() ist in Contao 4.13 als
 * veraltet gekennzeichnet und in Contao 5 ganz verschwunden. Damit dasselbe
 * Bundle unter beiden Versionen läuft, geht die Protokollierung über den
 * Monolog-Dienst des Kerns — und zwar nur dann, wenn er im Container
 * überhaupt vorhanden ist. Fehlt er (etwa in einem nackten Testaufbau ohne
 * vollständigen Container), passiert schlicht nichts, statt dass eine
 * Ausnahme den Seitenaufbau abbricht.
 */
final class Protokoll
{
	/**
	 * Vermerkt einen Fehler im Systemprotokoll (Kategorie ERROR).
	 *
	 * @param string $nachricht Klartext der Meldung, wie sie im Backend unter
	 *                          System -> Systemprotokoll erscheinen soll
	 * @param string $methode   Auslösende Methode, üblicherweise __METHOD__.
	 *                          Contao zeigt sie in der Spalte „Funktion“
	 *
	 * @return void Es gibt bewusst keine Rückmeldung: Ob das Protokoll
	 *              erreichbar war, darf den Zähler nicht interessieren
	 */
	public static function fehler(string $nachricht, string $methode): void
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('monolog.logger.contao.error'))
		{
			return;
		}

		$logger = $container->get('monolog.logger.contao.error');

		// Typprüfung statt blindem Aufruf: Ohne sie stünde hier ein Fatal
		// Error, falls der Dienst einmal etwas anderes liefert
		if (!$logger instanceof LoggerInterface)
		{
			return;
		}

		$logger->error(
			$nachricht,
			['contao' => new ContaoContext($methode, ContaoContext::ERROR)]
		);
	}
}
