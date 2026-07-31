<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 *
 * Ergänzt die Nachrichtenarchive um den Aufruf der Zugriffsstatistik.
 */

$GLOBALS['TL_DCA']['tl_news_archive']['list']['global_operations']['counter_news'] = [
	'label'      => ($GLOBALS['TL_LANG']['tl_news_archive']['counter_news'] ?? null),
	'href'       => 'key=counter',
	'icon'       => 'bundles/contaocounter/images/counter.png',
	'attributes' => 'onclick="Backend.getScrollOffset();"',
];
