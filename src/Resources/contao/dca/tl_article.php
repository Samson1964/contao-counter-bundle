<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 *
 * Ergänzt die Artikelübersicht um den Aufruf der Zugriffsstatistik.
 */

$GLOBALS['TL_DCA']['tl_article']['list']['global_operations']['counter_articles'] = [
	'label'      => &$GLOBALS['TL_LANG']['tl_article']['counter_articles'],
	'href'       => 'key=counter',
	'icon'       => 'bundles/contaocounter/images/counter.png',
	'attributes' => 'onclick="Backend.getScrollOffset();"',
];
