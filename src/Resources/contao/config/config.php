<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package   fh-counter
 * @author    Frank Hoppe
 * @license   GNU/LGPL
 * @copyright Frank Hoppe 2014
 */

$GLOBALS['FE_MOD']['fhcounter'] = array
(
	'fhcounter_register' => 'Schachbulle\ContaoCounterBundle\Classes\Register',
	'fhcounter_view'     => 'Schachbulle\ContaoCounterBundle\Classes\Frontend',
); 

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Schachbulle\ContaoCounterBundle\Classes\Tag', 'fhcounter');
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Schachbulle\ContaoCounterBundle\Classes\Tag', 'fhcounter_view');

// Backend-Module der Nachrichten erweitern
$GLOBALS['BE_MOD']['content']['news']['counter'] = array
(
	'Schachbulle\ContaoCounterBundle\Modules\StatistikNews', 'Statistik'
);

// Backend-Module der Seitenstruktur erweitern
$GLOBALS['BE_MOD']['design']['page']['counter'] = array
(
	'Schachbulle\ContaoCounterBundle\Modules\StatistikPages', 'Statistik'
);

/**
 * -------------------------------------------------------------------------
 * Voreinstellungen
 * -------------------------------------------------------------------------
 */

$GLOBALS['TL_CONFIG']['counter_topx_news'] = '100';
$GLOBALS['TL_CONFIG']['counter_topx_pages'] = '100';
