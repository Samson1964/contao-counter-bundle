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

$GLOBALS['TL_LANG']['tl_settings']['counter_mail'] = ['Send statistics by e-mail', 'A daily cron job sends yesterday\'s top list, on Mondays also last week\'s and on the first of a month also last month\'s. Each content type is sent to its own distribution list.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_anzahl'] = ['Number of entries', 'How many entries the top list in the e-mail shows.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_template'] = ['Template', 'Template of the e-mail. Own versions in the templates/ folder starting with "counter_mail_" appear here.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absender'] = ['Sender address', 'If left empty, the administrator address from the Contao settings is used.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absendername'] = ['Sender name', 'Name shown as the sender.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_betreff'] = ['Subject prefix', 'Fixed text placed in front of every subject line, e.g. "[Web statistics]".'];

$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_page'] = ['Recipients of the page statistics', 'One address per line or separated by commas. The notation "Name &lt;address@example.org&gt;" is allowed. Without recipients the page statistics are not sent.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_page'] = ['Page statistics carbon copy', 'Further addresses receiving a copy of the page statistics. Same notation as next to it.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_article'] = ['Recipients of the article statistics', 'One address per line or separated by commas. Without recipients the article statistics are not sent.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_article'] = ['Article statistics carbon copy', 'Further addresses receiving a copy of the article statistics.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_news'] = ['Recipients of the news statistics', 'One address per line or separated by commas. Without recipients the news statistics are not sent.'];
$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_news'] = ['News statistics carbon copy', 'Further addresses receiving a copy of the news statistics.'];
