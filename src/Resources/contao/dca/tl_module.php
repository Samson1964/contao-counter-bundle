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
 * Paletten der beiden Frontend-Module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['fhcounter_register'] =
	'{title_legend},name,type;'
	.'{counter_legend},fhc_register_pages,fhc_register_articles,fhc_register_news,'
	.'fhc_onlinetime,fhc_registernewtime,fhc_register_be_user;'
	.'{expert_legend:hide},cssID,align,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['fhcounter_view'] =
	'{title_legend},name,headline,type;'
	.'{info_legend},fhc_view_pages,fhc_view_articles,fhc_view_news,fhc_infos_counter,'
	.'fhc_infos_debug,fhc_view_diagrams,fhc_view_tables,fhc_1000_separator;'
	.'{template_legend:hide},fhc_template;'
	.'{protected_legend:hide},protected;'
	.'{expert_legend:hide},cssID,align,space';

/**
 * Felder des Zählermoduls
 */

// Zählung tl_page ja/nein
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_register_pages'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_register_pages'] ?? null),
	'inputType' => 'checkbox',
	'default'   => true,
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Zählung tl_article ja/nein
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_register_articles'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_register_articles'] ?? null),
	'inputType' => 'checkbox',
	'default'   => true,
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Zählung tl_news ja/nein
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_register_news'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_register_news'] ?? null),
	'inputType' => 'checkbox',
	'default'   => true,
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Sekunden, die ein Besucher als online gilt
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_onlinetime'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_onlinetime'] ?? null),
	'inputType' => 'text',
	'default'   => '120',
	'eval'      => ['rgxp' => 'digit', 'tl_class' => 'w50 clr'],
	'sql'       => "smallint(5) unsigned NOT NULL default '120'",
];

// Sekunden, nach denen ein Besucher erneut gezählt wird
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_registernewtime'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_registernewtime'] ?? null),
	'inputType' => 'text',
	'default'   => '900',
	'eval'      => ['rgxp' => 'digit', 'tl_class' => 'w50'],
	'sql'       => "smallint(5) unsigned NOT NULL default '900'",
];

// Angemeldete Backend-Benutzer mitzählen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_register_be_user'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_register_be_user'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 clr', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

/**
 * Felder des Ausgabemoduls
 */

// Tausenderpunkte setzen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_1000_separator'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_1000_separator'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Allgemeine Zählerinformationen anzeigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_infos_counter'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_infos_counter'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 clr', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Diagnoseangaben anzeigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_infos_debug'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_infos_debug'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Zähler tl_page bei der Ausgabe berücksichtigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_view_pages'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_view_pages'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Zähler tl_article bei der Ausgabe berücksichtigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_view_articles'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_view_articles'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Zähler tl_news bei der Ausgabe berücksichtigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_view_news'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_view_news'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Diagramme des Standardzählers anzeigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_view_diagrams'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_view_diagrams'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 clr', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Tabellen aller Zähler anzeigen
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_view_tables'] = [
	'label'     => ($GLOBALS['TL_LANG']['tl_module']['fhc_view_tables'] ?? null),
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50', 'isBoolean' => true],
	'sql'       => "char(1) NOT NULL default ''",
];

// Ausgabetemplate
$GLOBALS['TL_DCA']['tl_module']['fields']['fhc_template'] = [
	'label'            => ($GLOBALS['TL_LANG']['tl_module']['fhc_template'] ?? null),
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => ['tl_module_fhcounter', 'getCounterTemplates'],
	'eval'             => ['tl_class' => 'w50', 'includeBlankOption' => true],
	'sql'              => "varchar(64) NOT NULL default ''",
];

/**
 * Hilfsmethoden für die Modulfelder des Zählers.
 */
class tl_module_fhcounter extends Backend
{
	/**
	 * Liefert alle Ausgabetemplates des Zählers.
	 *
	 * Gefunden werden die mitgelieferten Templates ebenso wie eigene Fassungen
	 * im Ordner templates/, sofern ihr Name mit „fhcounter_“ beginnt.
	 *
	 * @return array Zuordnung Dateiname => Dateiname, wie von Contao für
	 *               Auswahllisten erwartet
	 */
	public function getCounterTemplates(): array
	{
		return $this->getTemplateGroup('fhcounter_');
	}
}
