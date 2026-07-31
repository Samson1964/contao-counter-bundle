<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoCounterBundle\Helper;

/**
 * Erzeugt Balkendiagramme als SVG.
 *
 * Die Diagramme entstehen vollständig auf dem Server. Das ersetzt die früher
 * eingebundene Javascript-Bibliothek „flot“, die eine jQuery-Abhängigkeit
 * mitbrachte und deren Pfade seit dem Wechsel auf Contao 4 ins Leere zeigten.
 * Ein SVG braucht keinerlei Javascript, funktioniert im Backend wie im
 * Frontend und lässt sich in E-Mails immerhin noch als Bild einbetten.
 */
final class Diagramm
{
	/**
	 * Standardfarbe der Balken (dasselbe Blau wie im Wertungsportal-Bundle,
	 * damit die Backend-Module einheitlich aussehen)
	 */
	public const FARBE = '#1F618D';

	/**
	 * Hellere Zweitfarbe für Vergleichsbalken
	 */
	public const FARBE_HELL = '#7FB3D5';

	/**
	 * Baut ein Balkendiagramm aus einer Werteliste.
	 *
	 * Die Balkenbreite ergibt sich aus der Anzahl der Werte: wenige Werte
	 * bekommen breitere Balken, viele schmalere. Ohne diese Anpassung säßen
	 * zwölf Monate als schmaler Streifen am linken Rand, während 30 Tage über
	 * den Rand hinausliefen.
	 *
	 * @param array  $balken       Liste aus ['titel' => Beschriftung, 'wert' => Zahl].
	 *                             Eine leere Liste ergibt einen leeren String,
	 *                             damit der Aufrufer einen Hinweis zeigen kann
	 *                             statt eines leeren Rahmens
	 * @param string $beschriftung Kurztext für die Vorlesehilfe (aria-label),
	 *                             z. B. „Zugriffe je Stunde“
	 * @param string $farbe        Füllfarbe der Balken als Hex-Wert
	 * @param int    $hoehe        Gesamthöhe des Diagramms in Pixeln
	 * @param bool   $schraeg      Beschriftung um 45 Grad drehen. Nötig, sobald
	 *                             die Beschriftungen länger als zwei Zeichen
	 *                             sind (Datumsangaben), sonst überlappen sie
	 *
	 * @return string Fertiges SVG oder '' bei leerer Werteliste. Der Rückgabewert
	 *                ist bereits maskiert und kann roh ins Template
	 */
	public static function balken(array $balken, string $beschriftung, string $farbe = self::FARBE, int $hoehe = 220, bool $schraeg = false): string
	{
		if (!\count($balken))
		{
			return '';
		}

		$randLinks = 48;
		$randUnten = $schraeg ? 48 : 26;
		$randOben = 16;
		$abstand = 6;

		// Zielbreite, an der sich die Balkenbreite orientiert. Die tatsächliche
		// Breite ergibt sich danach aus der Balkenzahl
		$zielBreite = 900;
		$balkenBreite = (int) round(($zielBreite - $randLinks - 20) / max(1, \count($balken)) - $abstand);

		if ($balkenBreite > 48)
		{
			$balkenBreite = 48;
		}

		if ($balkenBreite < 8)
		{
			$balkenBreite = 8;
		}

		$breite = $randLinks + \count($balken) * ($balkenBreite + $abstand) + 20;
		$nutzHoehe = $hoehe - $randOben - $randUnten;

		// Runde Skala bestimmen, damit die Beschriftung der Hilfslinien
		// glatte Zahlen zeigt statt krummer Bruchteile des Höchstwerts
		$max = 0;

		foreach ($balken as $b)
		{
			$max = max($max, (int) $b['wert']);
		}

		$maxSkala = self::skala($max);

		// display:block ist wichtig — ein SVG ist von Haus aus ein
		// Inline-Element und stellt sich sonst wie Text neben schwebende Inhalte
		$svg = [];
		$svg[] = '<svg viewBox="0 0 '.$breite.' '.$hoehe.'" width="100%" height="'.$hoehe.'" role="img" aria-label="'
			.htmlspecialchars($beschriftung, ENT_QUOTES).'" xmlns="http://www.w3.org/2000/svg"'
			.' style="display:block;max-width:'.$breite.'px;min-width:'.min($breite, 560).'px">';
		$svg[] = '<style>.fhc-achse{font:11px sans-serif;fill:#666}.fhc-wert{font:10px sans-serif;fill:#333}</style>';

		// Waagerechte Hilfslinien mit Beschriftung
		for ($i = 0; $i <= 4; ++$i)
		{
			$wert = $maxSkala / 4 * $i;
			$y = $randOben + $nutzHoehe - ($nutzHoehe / 4 * $i);

			$svg[] = '<line x1="'.$randLinks.'" y1="'.round($y, 1).'" x2="'.($breite - 10).'" y2="'.round($y, 1).'" stroke="#e2e2e2" stroke-width="1"/>';
			$svg[] = '<text x="'.($randLinks - 8).'" y="'.round($y + 4, 1).'" text-anchor="end" class="fhc-achse">'.round($wert).'</text>';
		}

		// Balken samt Beschriftung
		$x = $randLinks + $abstand / 2;
		// Werte nur beschriften, solange genug Platz ist
		$werteZeigen = $balkenBreite >= 16;

		foreach ($balken as $b)
		{
			$wert = (int) $b['wert'];
			$titel = htmlspecialchars((string) $b['titel'], ENT_QUOTES);
			$h = round($nutzHoehe * $wert / $maxSkala, 1);
			$y = $randOben + $nutzHoehe - $h;

			if ($h > 0)
			{
				$svg[] = '<rect x="'.$x.'" y="'.$y.'" width="'.$balkenBreite.'" height="'.$h.'" fill="'.$farbe
					.'"><title>'.$titel.': '.$wert.'</title></rect>';
			}

			if ($werteZeigen && $wert > 0)
			{
				$svg[] = '<text x="'.($x + $balkenBreite / 2).'" y="'.round($y - 4, 1).'" text-anchor="middle" class="fhc-wert">'.$wert.'</text>';
			}

			$xt = $x + $balkenBreite / 2;
			$yt = $randOben + $nutzHoehe + 14;

			if ($schraeg)
			{
				$svg[] = '<text x="'.$xt.'" y="'.$yt.'" text-anchor="end" class="fhc-achse" transform="rotate(-45 '.$xt.' '.$yt.')">'.$titel.'</text>';
			}
			else
			{
				$svg[] = '<text x="'.$xt.'" y="'.$yt.'" text-anchor="middle" class="fhc-achse">'.$titel.'</text>';
			}

			$x += $balkenBreite + $abstand;
		}

		// Grundlinie
		$svg[] = '<line x1="'.$randLinks.'" y1="'.($randOben + $nutzHoehe).'" x2="'.($breite - 10).'" y2="'
			.($randOben + $nutzHoehe).'" stroke="#999" stroke-width="1"/>';
		$svg[] = '</svg>';

		return implode("\n", $svg);
	}

	/**
	 * Rundet den Höchstwert auf eine gut teilbare Skalenobergrenze auf.
	 *
	 * Beispiele: 7 wird zu 8, 43 zu 60, 512 zu 600. Damit stehen an den vier
	 * Hilfslinien ganze Zahlen statt Werten wie 10,75.
	 *
	 * @param int $max Größter vorkommender Wert; 0 ist erlaubt
	 *
	 * @return int Obergrenze der Skala, immer mindestens 4 und immer durch 4 teilbar
	 */
	private static function skala(int $max): int
	{
		if ($max < 4)
		{
			return 4;
		}

		// Schrittweite eine Zehnerpotenz unterhalb der Größenordnung des
		// Höchstwerts, damit die Skala nicht weit über den Werten schwebt
		$schritt = (int) pow(10, max(0, \strlen((string) $max) - 2));
		$schritt = max(1, $schritt);

		return (int) (ceil($max / ($schritt * 4)) * $schritt * 4);
	}
}
