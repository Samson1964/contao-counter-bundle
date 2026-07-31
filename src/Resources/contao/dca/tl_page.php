<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 *
 * Ergänzt die Seitenstruktur um den Aufruf der Zugriffsstatistik.
 */

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['counter_pages'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_page']['counter_pages'],
	'href'       => 'key=counter',
	'icon'       => 'bundles/contaocounter/images/counter.png',
	'attributes' => 'onclick="Backend.getScrollOffset();"',
];
