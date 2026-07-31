<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

use Schachbulle\ContaoCounterBundle\Classes\Frontend;
use Schachbulle\ContaoCounterBundle\Classes\Register;
use Schachbulle\ContaoCounterBundle\Classes\Tag;
use Schachbulle\ContaoCounterBundle\Modules\StatistikArticles;
use Schachbulle\ContaoCounterBundle\Modules\StatistikNews;
use Schachbulle\ContaoCounterBundle\Modules\StatistikPages;

/**
 * Frontend-Module
 *
 * Das Zählermodul zählt, das Ausgabemodul zeigt an. Das Zählermodul muss im
 * Seitenlayout vor dem Ausgabemodul stehen.
 */
$GLOBALS['FE_MOD']['fhcounter'] = [
	'fhcounter_register' => Register::class,
	'fhcounter_view'     => Frontend::class,
];

/**
 * Insert-Tags {{fhcounter}} und {{fhcounterview}} als Ersatz für die Module
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [Tag::class, 'fhcounter'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [Tag::class, 'fhcounter_view'];

/**
 * Statistik-Ansichten in den drei Backend-Modulen.
 *
 * Der Schlüssel „counter“ wird von Contao aufgerufen, sobald in der
 * Adresszeile &key=counter steht; den Link dorthin setzen die DCA-Dateien
 * tl_page, tl_article und tl_news_archive als globale Operation.
 */
$GLOBALS['BE_MOD']['design']['page']['counter'] = [StatistikPages::class, 'Statistik'];
$GLOBALS['BE_MOD']['content']['article']['counter'] = [StatistikArticles::class, 'Statistik'];
$GLOBALS['BE_MOD']['content']['news']['counter'] = [StatistikNews::class, 'Statistik'];

/**
 * Stile der Backend-Statistik nur im Backend laden — im Frontend wären sie
 * totes Gewicht auf jeder einzelnen Seite
 */
if (Register::istBackend())
{
	$GLOBALS['TL_CSS'][] = 'bundles/contaocounter/css/backend.css';
}

/**
 * -------------------------------------------------------------------------
 * Voreinstellungen
 *
 * Gelten, solange unter System -> Einstellungen nichts anderes gespeichert ist.
 * -------------------------------------------------------------------------
 */

$GLOBALS['TL_CONFIG']['counter_topx_pages'] = 100;
$GLOBALS['TL_CONFIG']['counter_topx_articles'] = 100;
$GLOBALS['TL_CONFIG']['counter_topx_news'] = 100;
$GLOBALS['TL_CONFIG']['counter_mail_anzahl'] = 50;
$GLOBALS['TL_CONFIG']['counter_mail_template'] = 'counter_mail_standard';
