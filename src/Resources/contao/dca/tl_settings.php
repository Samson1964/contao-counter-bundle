<?php

/**
 * palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{counter_legend:hide},counter_topx_news,counter_topx_pages,counter_donotlog404,,counter_donotlogid';

/**
 * Felder
 */

$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_news'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['counter_topx_news'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'default'                 => 100,
	'options'                 => array(50, 100, 150, 200, 250, 300, 350, 400, 450, 500),
	'eval'                    => array
	(
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) NOT NULL default 100"
);

$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_pages'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['counter_topx_pages'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'default'                 => 100,
	'options'                 => array(50, 100, 150, 200, 250, 300, 350, 400, 450, 500),
	'eval'                    => array
	(
		'tl_class'            => 'w50'
	),
	'sql'                     => "int(10) NOT NULL default 100"
);

// Fehler 404 nicht im System-Log protokollieren
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_donotlog404'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['counter_donotlog404'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50 m12',
	)
);

// Fehler source_id fehlend nicht im System-Log protokollieren
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_donotlogid'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['counter_donotlogid'],
	'inputType'               => 'checkbox',
	'eval'                    => array
	(
		'tl_class'            => 'w50 m12',
	)
);
