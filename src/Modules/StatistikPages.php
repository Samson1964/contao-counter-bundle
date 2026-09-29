<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Modules;

use Contao\Database;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Backend-Statistik der Seitenzugriffe.
 *
 * Erreichbar über Seitenstruktur -> Statistik (contao?do=page&key=counter).
 *
 * Als einzige der drei Statistiken lässt sie sich auf einen Seitenpfad
 * beschränken — auf den Teilbaum, der in der Seitenstruktur gerade
 * eingestellt ist.
 *
 * @phpstan-import-type Seitenpfad from Statistik
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

	/**
	 * Die Seitenstatistik bietet die Beschränkung auf einen Seitenpfad an.
	 *
	 * @return bool Immer true
	 */
	protected function kenntSeitenpfad(): bool
	{
		return true;
	}

	/**
	 * Liefert den in der Seitenstruktur eingestellten Seitenpfad.
	 *
	 * Gemeint ist die Auswahl, die Contao über den Link „Nur diesen Knoten
	 * anzeigen“ am Seitennamen trifft und oberhalb des Seitenbaums als
	 * Brotkrumenpfad zeigt. Contao legt sie in der Backend-Sitzung unter
	 * tl_page_node ab; genau dieser Wert wird hier gelesen, damit Statistik und
	 * Seitenbaum dieselbe Auswahl verwenden und nichts doppelt einzustellen ist.
	 *
	 * Die Prüfung, ob der Benutzer diesen Knoten überhaupt sehen darf, hat
	 * Contao beim Setzen bereits vorgenommen — ein Knoten außerhalb der
	 * Seitenfreigaben wird dort mit einer Zugriffsverweigerung abgewiesen und
	 * gar nicht erst gespeichert.
	 *
	 * @return array|null Knoten-ID, Titel von der Wurzel bis zum Knoten und
	 *                    die IDs aller Seiten im Teilbaum einschließlich des
	 *                    Knotens selbst. null, wenn kein Knoten eingestellt
	 *                    ist oder die eingestellte Seite nicht mehr existiert
	 *
	 * @phpstan-return Seitenpfad|null
	 */
	protected function seitenpfad(): ?array
	{
		$knoten = self::eingestellterKnoten();

		if ($knoten < 1)
		{
			return null;
		}

		$db = Database::getInstance();
		$titel = [];
		$id = $knoten;

		// Vom Knoten aufwärts bis zur Wurzelseite, wie Contaos Brotkrumen.
		// Die Obergrenze schützt vor einer Endlosschleife, falls die
		// Seitenstruktur je einen Kreis enthalten sollte
		for ($schritt = 0; $id > 0 && $schritt < 50; ++$schritt)
		{
			$seite = $db->prepare('SELECT pid, title, type FROM tl_page WHERE id=?')->limit(1)->execute($id);

			if ($seite->numRows < 1)
			{
				break;
			}

			array_unshift($titel, (string) $seite->title);

			if ('root' === $seite->type)
			{
				break;
			}

			$id = (int) $seite->pid;
		}

		// Die eingestellte Seite selbst ist gelöscht worden. Contao setzt den
		// Knoten erst beim nächsten Aufruf der Seitenstruktur zurück
		if (!$titel)
		{
			return null;
		}

		$ids = [$knoten];

		foreach ($db->getChildRecords($knoten, 'tl_page') as $kind)
		{
			$ids[] = (int) $kind;
		}

		return ['knoten' => $knoten, 'pfad' => $titel, 'ids' => $ids];
	}

	/**
	 * Liest den in der Seitenstruktur gewählten Knoten aus der Sitzung.
	 *
	 * Die Sitzung wird über den request_stack geholt, weil das in Contao 4.13
	 * und 5 gleichermaßen funktioniert; den früheren Dienst „session“ gibt es
	 * in Contao 5 nicht mehr.
	 *
	 * @return int ID der gewählten Seite, 0 wenn keine gewählt ist oder keine
	 *             Sitzung besteht (etwa beim Aufruf von der Konsole)
	 */
	private static function eingestellterKnoten(): int
	{
		$container = System::getContainer();

		if (null === $container || !$container->has('request_stack'))
		{
			return 0;
		}

		$stapel = $container->get('request_stack');

		if (!$stapel instanceof RequestStack)
		{
			return 0;
		}

		try
		{
			$ablage = $stapel->getSession()->getBag('contao_backend');
		}
		catch (\Throwable $e)
		{
			// Keine Sitzung oder kein Contao-Backend-Speicher vorhanden
			return 0;
		}

		if (!$ablage instanceof AttributeBagInterface)
		{
			return 0;
		}

		$wert = $ablage->get('tl_page_node');

		return is_numeric($wert) ? (int) $wert : 0;
	}
}
