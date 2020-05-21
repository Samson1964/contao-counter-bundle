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

