<?php

declare(strict_types=1);

/**
 * Counter for Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 */

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_module']['counter_legend'] = 'Counter settings';
$GLOBALS['TL_LANG']['tl_module']['info_legend'] = 'Output settings';

/**
 * Fields of the counting module
 */
$GLOBALS['TL_LANG']['tl_module']['fhc_register_pages'] = ['Count page requests', 'Count requests for pages (tl_page).'];
$GLOBALS['TL_LANG']['tl_module']['fhc_register_articles'] = ['Count article requests', 'Count requests for articles (tl_article).'];
$GLOBALS['TL_LANG']['tl_module']['fhc_register_news'] = ['Count news requests', 'Count requests for news items (tl_news).'];
$GLOBALS['TL_LANG']['tl_module']['fhc_onlinetime'] = ['Online time in seconds', 'After this time a visitor is no longer considered online.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_registernewtime'] = ['Counting lock in seconds', 'A returning visitor is not counted again within this time.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_register_be_user'] = ['Include back end users', 'Count requests of users who are logged into the back end.'];

/**
 * Fields of the output module
 */
$GLOBALS['TL_LANG']['tl_module']['fhc_infos_counter'] = ['General information', 'Show details of this counter, e.g. first request and total hits.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_infos_debug'] = ['Diagnostic information', 'Show diagnostic details of this counter, e.g. visitor addresses.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_pages'] = ['Page requests', 'Show requests for pages.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_articles'] = ['Article requests', 'Show requests for articles.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_news'] = ['News requests', 'Show requests for news items.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_diagrams'] = ['Charts', 'Show the default counter as charts.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_view_tables'] = ['Tables', 'Show all counters as tables.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_template'] = ['Template', 'Choose the template to be used.'];
$GLOBALS['TL_LANG']['tl_module']['fhc_1000_separator'] = ['Thousands separator', 'Separate thousands with a dot.'];
