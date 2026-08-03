<?php

declare(strict_types=1);

/**
 * Counter für Contao Open Source CMS
 *
 * @author    Frank Hoppe
 * @license   LGPL-3.0-or-later
 *
 * Hinweis zu den Labels: Sie werden als Referenz eingebunden (&$GLOBALS[…]).
 * Das ist wichtig, weil der DcaLoader beim Aufruf von contao:migrate keine
 * Sprachdateien lädt: Ein Referenzzugriff auf einen fehlenden Schlüssel legt
 * ihn stillschweigend an und trägt die Beschriftung nach, sobald die
 * Sprachdatei kommt. Ein lesender Zugriff — auch mit „?? null“ abgesichert —
 * würde den Wert dagegen beim Laden des DCA einfrieren.
 */

use Contao\Backend;

/**
 * Paletten
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';'
	.'{counter_legend:hide},counter_topx_pages,counter_topx_articles,counter_topx_news,counter_donotlog404,counter_donotlogid;'
	.'{counter_mail_legend:hide},counter_mail';

/**
 * Auswahlfeld anmelden.
 *
 * Ohne diesen Eintrag lehnt Contao das Ein- und Ausklappen der Unterpalette mit
 * „Bad request“ (HTTP 400) ab: Ajax::executePostActions prüft bei
 * toggleSubpalette, ob das Feld in __selector__ steht, und bricht sonst ab.
 * Im Backend hängt dann die Meldung „Die Daten werden geladen …“ endlos.
 * Die Zuweisung mit [] ist Absicht — tl_settings bringt von Haus aus keine
 * Selektorenliste mit, andere Erweiterungen können aber schon eine angelegt haben.
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['__selector__'][] = 'counter_mail';

/**
 * Unterpalette: Die Einstellungen erscheinen erst, wenn der Versand
 * eingeschaltet ist — sonst stünden zehn Felder ohne Wirkung herum.
 *
 * Aufbau, Absender und Betreff gelten für alle drei Statistiken gemeinsam;
 * die Empfänger werden je Inhaltsart getrennt gepflegt. Wer die Seiten-,
 * Artikel- und Nachrichtenstatistik bekommt, ist selten dieselbe Runde.
 * Eine Inhaltsart ohne Empfänger wird übersprungen — damit ist zugleich
 * gesagt, welche Statistiken überhaupt hinausgehen.
 */
$GLOBALS['TL_DCA']['tl_settings']['subpalettes']['counter_mail'] =
	'counter_mail_anzahl,counter_mail_template,'
	.'counter_mail_absender,counter_mail_absendername,counter_mail_betreff,'
	.'counter_mail_empfaenger_page,counter_mail_kopie_page,'
	.'counter_mail_empfaenger_article,counter_mail_kopie_article,'
	.'counter_mail_empfaenger_news,counter_mail_kopie_news';

/**
 * Felder
 */

// Zahl der Einträge in der Seiten-Bestenliste
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_pages'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_topx_pages'],
	'exclude'   => true,
	'inputType' => 'select',
	'default'   => 100,
	'options'   => [25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
	'eval'      => ['tl_class' => 'w50'],
];

// Zahl der Einträge in der Artikel-Bestenliste
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_articles'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_topx_articles'],
	'exclude'   => true,
	'inputType' => 'select',
	'default'   => 100,
	'options'   => [25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
	'eval'      => ['tl_class' => 'w50'],
];

// Zahl der Einträge in der Nachrichten-Bestenliste
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_topx_news'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_topx_news'],
	'exclude'   => true,
	'inputType' => 'select',
	'default'   => 100,
	'options'   => [25, 50, 100, 150, 200, 250, 300, 350, 400, 450, 500],
	'eval'      => ['tl_class' => 'w50 clr'],
];

// Fehler 404 nicht im Systemprotokoll vermerken
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_donotlog404'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_donotlog404'],
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12 clr'],
];

// Fehlende Quell-ID nicht im Systemprotokoll vermerken
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_donotlogid'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_donotlogid'],
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12'],
];

// Hauptschalter des täglichen Mailversands
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail'],
	'inputType' => 'checkbox',
	'eval'      => ['tl_class' => 'w50 m12', 'submitOnChange' => true],
];

// Zahl der Listenplätze in der Mail
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_anzahl'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_anzahl'],
	'inputType' => 'select',
	'default'   => 50,
	'options'   => [10, 20, 25, 50, 100],
	'eval'      => ['tl_class' => 'w50'],
];

// Vorlage der Mail
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_template'] = [
	'label'            => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_template'],
	'inputType'        => 'select',
	'options_callback' => ['tl_settings_counter', 'getMailTemplates'],
	'eval'             => ['tl_class' => 'w50 clr', 'includeBlankOption' => true],
];

// Absenderadresse
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_absender'] = [
	'label'     => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absender'],
	'inputType' => 'text',
	'eval'      => ['rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50 clr'],
];

// Absendername
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_absendername'] = [
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_absendername'],
	'inputType'     => 'text',
	'eval'          => ['maxlength' => 255, 'tl_class' => 'w50'],
	'save_callback' => [['tl_settings_counter', 'entschluesseln']],
];

// Fester Zusatz vor der Betreffzeile
$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_betreff'] = [
	'label'         => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_betreff'],
	'inputType'     => 'text',
	'eval'          => ['maxlength' => 128, 'tl_class' => 'w50 clr'],
	'save_callback' => [['tl_settings_counter', 'entschluesseln']],
];

/**
 * Empfänger je Inhaltsart.
 *
 * Die Namensendungen page, article und news entsprechen den Bezeichnern der
 * Backend-Module; Cron\Statistikmail setzt daraus über Helper\Inhalte den
 * Namen der Einstellung zusammen. Wer hier eine Endung ändert, muss dort
 * nichts anpassen — wohl aber die Zuordnung in Helper\Inhalte.
 */
foreach (['page', 'article', 'news'] as $art)
{
	// Empfänger dieser Statistik, mehrere durch Komma oder Zeilenumbruch getrennt
	$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_empfaenger_'.$art] = [
		'label'         => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_empfaenger_'.$art],
		'inputType'     => 'textarea',
		'eval'          => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'w50 clr'],
		'save_callback' => [['tl_settings_counter', 'entschluesseln']],
	];

	// Empfänger in Kopie
	$GLOBALS['TL_DCA']['tl_settings']['fields']['counter_mail_kopie_'.$art] = [
		'label'         => &$GLOBALS['TL_LANG']['tl_settings']['counter_mail_kopie_'.$art],
		'inputType'     => 'textarea',
		'eval'          => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'w50'],
		'save_callback' => [['tl_settings_counter', 'entschluesseln']],
	];
}

unset($art);

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
	 *
	 * @phpstan-return array<string, string>
	 */
	public function getMailTemplates(): array
	{
		return $this->getTemplateGroup('counter_mail_');
	}

	/**
	 * Macht HTML-Entities vor dem Speichern wieder zu Klartext.
	 *
	 * Contaos Input::post() ruft stripTags() auf, und zwar unabhängig von der
	 * Feldeinstellung decodeEntities. Die spitze Klammer in
	 * „Name <adresse@example.org>“ sieht für stripTags wie ein unbekanntes
	 * Tag aus und wird zu „&lt;“, während die schließende Klammer stehen
	 * bleibt. Gespeichert stünde dort „Name &lt;adresse@example.org>“ — für
	 * den Mailversand unbrauchbar und im Eingabefeld unschön anzusehen.
	 *
	 * Der Rückweg über preserveTags wäre die Alternative, würde aber
	 * ungefiltertes HTML in die Einstellung lassen. Hier genügt es, die
	 * Entities beim Speichern aufzulösen.
	 *
	 * @param mixed $wert Wert, wie ihn das Widget geliefert hat
	 *
	 * @return string Derselbe Wert als Klartext
	 */
	public function entschluesseln($wert): string
	{
		return html_entity_decode((string) $wert, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}
