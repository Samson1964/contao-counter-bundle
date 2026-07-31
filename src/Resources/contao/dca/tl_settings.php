<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 *
 * Hinweis zu den Labels: Sie werden bewusst NICHT als Referenz eingebunden.
 * Der DcaLoader lädt beim Aufruf von contao:migrate keine Sprachdateien, und
 * ein Referenzzugriff auf einen fehlenden Schlüssel erzeugt dort Warnungen.
 */

use Contao\Backend;

/**
 * Paletten
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';'
	.'{counter_legend:hide},counter_topx_pages,counter_topx_articles,counter_topx_news,counter_donotlog404,counter_donotlogid;'
	.'{counter_mail_legend:hide},counter_mail';

/**
 * Unterpalette: Die Adressfelder erscheinen erst, wenn der Versand
 * eingeschaltet ist — sonst stehen sieben Felder ohne Wirkung herum
 */
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['counter_mail'] =
	'counter_mail_quellen,counter_mail_anzahl,counter_mail_template,'
	.'counter_mail_absender,counter_mail_absendername,'
	.'counter_mail_empfaenger,counter_mail_kopie,counter_mail_betreff';

/**
 * Felder
 */

// Zahl der Einträge in der Seiten-Bestenliste
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_pages'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_topx_pages'] ?? null),
	'exclude'   => true,
	'inputType' => 'select',
	'default'   => 100,
	'options'   => [25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
	'eval'      => ['tl_class' => 'w50'],
];

// Zahl der Einträge in der Artikel-Bestenliste
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_articles'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_topx_articles'] ?? null),
	'exclude'   => true,
	'inputType' => 'select',
	'default'   => 100,
	'options'   => [25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
	'eval'      => ['tl_class' => 'w50'],
];

// Zahl der Einträge in der Nachrichten-Bestenliste
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_news'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_topx_news'] ?? null),
	'exclude'   => true,
	'inputType' => 'select',
	'default'   => 100,
	'options'   => [25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
	'eval'      => ['tl_class' => 'w50 clr'],
];

// Fehler 404 nicht im Systemprotokoll vermerken
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_donotlog404'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_donotlog404'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12 clr'],
];

// Fehlende Quell-ID nicht im Systemprotokoll vermerken
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_donotlogid'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_donotlogid'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12'],
];

// Hauptschalter des täglichen Mailversands
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12', 'submitOnChange' => true],
];

// Welche Inhaltsarten sollen verschickt werden?
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_quellen'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_quellen'] ?? null),
	'inputType' => 'checkbox',
	'options'   => ['tl_page', 'tl_article', 'tl_news'],
	'reference' => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_quellen_optionen'] ?? null),
	'eval'      => ['multiple' => true, 'tl_class' => 'w50 clr'],
];

// Zahl der Listenplätze in der Mail
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_anzahl'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_anzahl'] ?? null),
	'inputType' => 'select',
	'default'   => 50,
	'options'   => [10, 20, 25, 50, 100],
	'eval'      => ['tl_class' => 'w50'],
];

// Vorlage der Mail
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_template'] = [
	'label'            => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_template'] ?? null),
	'inputType'        => 'select',
	'options_callback' => ['tl_settings_counter', 'getMailTemplates'],
	'eval'             => ['tl_class' => 'w50 clr', 'includeBlankOption' => true],
];

// Absenderadresse
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_absender'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_absender'] ?? null),
	'inputType' => 'text',
	'eval'      => ['rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50 clr'],
];

// Absendername
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_absendername'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_absendername'] ?? null),
	'inputType' => 'text',
	'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
];

// Empfänger, mehrere durch Komma getrennt
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_empfaenger'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger'] ?? null),
	'inputType' => 'textarea',
	'eval'      => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
];

// Empfänger in Kopie, mehrere durch Komma getrennt
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_kopie'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie'] ?? null),
	'inputType' => 'textarea',
	'eval'      => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
];

// Fester Zusatz vor der Betreffzeile
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_betreff'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_settings']['counter_mail_betreff'] ?? null),
	'inputType' => 'text',
	'eval'      => ['maxlength' => 128, 'tl_class' => 'w50 clr'],
];

/**
 * Hilfsmethoden für die Einstellungen des Zählers.
 */
class tl_settings_counter extends Backend
{
	/**
	 * Liefert alle Vorlagen für die Statistik-Mail.
	 *
	 * Gefunden werden sowohl die mitgelieferte Vorlage als auch eigene
	 * Fassungen im Ordner templates/, sofern ihr Name mit „counter_mail_“
	 * beginnt.
	 *
	 * @return array Zuordnung Dateiname => Dateiname, wie von Contao für
	 *               Auswahllisten erwartet
	 */
	public function getMailTemplates(): array
	{
		return $this->getTemplateGroup('counter_mail_');
	}
}
