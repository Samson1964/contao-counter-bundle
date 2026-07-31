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
$GLOBALS['TL_LANG']['tl_settings']['counter_legend'] = 'Counter';
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_legend'] = 'Counter: statistics by e-mail';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_settings']['counter_topx_pages'] = ['Number of pages', 'How many pages the top list shows.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_topx_articles'] = ['Number of articles', 'How many articles the top list shows.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_topx_news'] = ['Number of news items', 'How many news items the top list shows.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_donotlog404'] = ['Do not log 404 errors', 'Do not record requests for missing pages in the system log.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_donotlogid'] = ['Do not log missing source IDs', 'Do not record it when the counter could not identify the content.'];

$GLOBALS['TL_LANG']['tl_settings']['counter_mail'] = ['Send statistics by e-mail', 'A daily cron job sends yesterday\'s top list, on Mondays also last week\'s and on the first of a month also last month\'s.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_quellen'] = ['Content types', 'Which content types get their own e-mail.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_quellen_optionen'] = [
	'tl_page'    => 'Pages',
	'tl_article' => 'Articles',
	'tl_news'    => 'News',
];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_anzahl'] = ['Number of entries', 'How many entries the top list in the e-mail shows.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_template'] = ['Template', 'Template of the e-mail. Own versions in the templates/ folder starting with "counter_mail_" appear here.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absender'] = ['Sender address', 'If left empty, the administrator address from the Contao settings is used.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absendername'] = ['Sender name', 'Name shown as the sender.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger'] = ['Recipients', 'One address per line or separated by commas. The notation "Name &lt;address@example.org&gt;" is allowed. Without recipients nothing is sent.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie'] = ['Carbon copy', 'Further addresses receiving a copy. Same notation as above.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_betreff'] = ['Subject prefix', 'Fixed text placed in front of every subject line, e.g. "[Web statistics]".'];
