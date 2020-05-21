<?php

/**
 * Contao Open Source CMS, Copyright (C) 2005-2013 Leo Feyer
 *
 *
 * Supported GET parameters:
 * - bid:   Banner ID
 *
 * Usage example:
 * <a href="system/modules/banner/public/conban_clicks.php?bid=7">
 *
 * @copyright	Glen Langer 2007..2013 <http://www.contao.glen-langer.de>
 * @author      Glen Langer (BugBuster)
 * @package     Banner
 * @license     LGPL
 * @filesource
 */

/**
 * Run in a custom namespace, so the class can be replaced
 */
use Contao\Controller;

/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
// ER2 / ER3 (dev over symlink)
if(file_exists('../../../initialize.php')) require('../../../initialize.php');
else require('../../../../../system/initialize.php');


/**
 * Class BannerClicks
 *
 * Banner ReDirect class
 * @copyright  Glen Langer 2007..2013
 * @author     Glen Langer
 * @package    Banner
 */
class Statistik
{
	public function run()
	{
		$modus = \Input::get('mode');
		$id = \Input::get('id');
		
		if($modus == 'news' && $id)
		{
			// Zähler für Nachricht einlesen
			$ergebnis = \Database::getInstance()->prepare("SELECT * FROM tl_fh_counter WHERE source = ? AND pid = ?")
												->limit(1)
												->execute('tl_news', $id);
			
			// Zähler auswerten
			if($ergebnis->numRows == 1)
			{
				$arrData = unserialize($ergebnis->counter);
				
				echo '<!DOCTYPE html>';
				echo '<html lang="de">';
				echo '<head>';
				echo '<meta charset="utf-8">';
				echo '<title>Zugriffsstatistik für Nachricht '.$id.'</title>';
				echo '</head>';
				echo '<body>';
				echo '<h1>Zugriffsstatistik für Nachricht '.$id.'</h1>';
				echo date('d.m.Y H:i:s');
				echo '<p><b>'.$arrData['all'].' Zugriffe insgesamt</b></p>';
				
				// Jahre ablaufen
				foreach($arrData as $key => $value)
				{
					if($key != 'all') 
					{
						$jahr[$key] = $value['all']; // Jahr zuordnen
						// Monate des Jahres ablaufen
						foreach($arrData[$key] as $keyMonth => $valueMonth)
						{
							if($keyMonth != 'all') 
							{
								$monat[$key.'-'.substr('0'.$keyMonth, -2)] = $valueMonth['all']; // Monat zuordnen
								// Tage des Monats ablaufen
								foreach($arrData[$key][$keyMonth] as $keyDay => $valueDay)
								{
									if($keyDay != 'all') 
									{
										$tag[$key.'-'.substr('0'.$keyMonth, -2).'-'.substr('0'.$keyDay, -2)] = $valueDay['all']; // Tag zuordnen
									}
								}
							}
						}
					}
				}
				
				// Ausgabe-Arrays sortieren
				ksort($jahr);
				ksort($monat);
				ksort($tag);
				
				echo '<h2>Zugriffe je Jahr</h2>';
				echo '<table border="1">';
				echo '<tr><th>Jahr</th><th>Zugriffe</th></tr>';
				foreach($jahr as $key => $value)
				{
					echo '<tr>';
					echo '<td>'.$key.'</td>';
					echo '<td>'.$value.'</td>';
					echo '</tr>';
				}
				echo '</table>';

				echo '<h2>Zugriffe je Monat</h2>';
				echo '<table border="1">';
				echo '<tr><th>Monat</th><th>Zugriffe</th></tr>';
				foreach($monat as $key => $value)
				{
					echo '<tr>';
					echo '<td>'.$key.'</td>';
					echo '<td>'.$value.'</td>';
					echo '</tr>';
				}
				echo '</table>';

				echo '<h2>Zugriffe je Tag</h2>';
				echo '<table border="1">';
				echo '<tr><th>Datum</th><th>Zugriffe</th></tr>';
				foreach($tag as $key => $value)
				{
					echo '<tr>';
					echo '<td>'.$key.'</td>';
					echo '<td>'.$value.'</td>';
					echo '</tr>';
				}
				echo '</table>';

				//echo '<pre>';
				//print_r($jahr);
				//print_r($monat);
				//print_r($tag);
				//echo '</pre>';
				echo '</body>';
				echo '</html>';

				//print_r(unserialize($ergebnis->counter));
			}
		}
	}
}

/**
 * Instantiate controller
 */
$objStatistik = new Statistik();
$objStatistik->run();

