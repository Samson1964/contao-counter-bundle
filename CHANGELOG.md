# Counter Changelog

## Version 2.0.2 (2026-07-31)

Inhaltlich gleich mit der zurückgezogenen 2.0.1: Deren Tag war versehentlich auf
den Stand von 2.0.0 gesetzt worden. Packagist hatte die falsche Zuordnung bereits
zwischengespeichert und liest einen einmal bekannten Tag nicht neu ein, deshalb
ist 2.0.1 ersatzlos entfallen.

* Fix: Der Haken „Statistik per E-Mail verschicken“ unter System &rarr; Einstellungen
  ließ sich nicht setzen — die Meldung „Die Daten werden geladen …“ blieb stehen
  und der Ajax-Aufruf endete mit HTTP 400. Das Feld war als Umschalter für eine
  Unterpalette gedacht, aber nicht als Auswahlfeld angemeldet; Contao weist einen
  `toggleSubpalette`-Aufruf ab, wenn das Feld nicht in
  `$GLOBALS['TL_DCA'][…]['palettes']['__selector__']` steht
* Fix: Beschriftungen der DCA-Felder wieder als Referenz eingebunden
  (`&$GLOBALS['TL_LANG'][…]`). In Fassung 2.0.0 standen dort abgesicherte
  Lesezugriffe, die den Wert beim Laden des DCA einfrieren — je nach
  Ladereihenfolge wären Feldbeschriftungen und die Klartexte der Optionen leer
  geblieben. Die Referenzform erzeugt auch beim `contao:migrate` ohne
  Sprachdateien keine Warnungen (nachgemessen)

## Version 2.0.0 (2026-07-31)

Diese Fassung setzt Contao 4.13 oder Contao 5 und PHP 7.4 oder neuer voraus.
Nach dem Einspielen sind ein Datenbankabgleich (`contao:migrate`) und
`contao:assets:install` nötig.

* Add: Zugriffsstatistik auch für **Artikel** (Artikel &rarr; Statistik)
* Add: Verlaufsdiagramm in allen drei Backend-Statistiken — Stunden eines Tages,
  Tage eines Monats, Monate eines Jahres, als SVG ohne Javascript
* Add: Neue Kopfnavigation der Statistiken mit Tag/Monat/Jahr-Umschaltung,
  Vor- und Zurückschritt sowie Gesamtzahl der Zugriffe im Zeitraum
* Add: Täglicher Cronjob verschickt die Bestenlisten per E-Mail (Vortag, montags
  die Vorwoche, am Monatsersten der Vormonat). Ersetzt die drei externen Skripte
  in `web/php`, die seit Contao 4.5 nicht mehr lauffähig waren
* Add: Einstellungen für den Mailversand unter System &rarr; Einstellungen
  (Inhalte, Listenplätze, Vorlage, Absender, Empfänger, Kopie, Betreffzusatz)
* Add: Mailvorlage `counter_mail_standard`; eigene Fassungen mit dem Namensanfang
  `counter_mail_` unter `templates/` erscheinen in der Auswahlliste
* Add: Einstellung `counter_topx_articles` für die Länge der Artikel-Bestenliste
* Add: Englische Sprachdateien für Einstellungen, Module und Modulfelder
* Fix: **Der erste Zugriff eines Tages ging verloren.** Tages- und Stundenwert
  wurden beim Anlegen auf 0 statt auf 1 gesetzt, wodurch Tages- und Monatssumme
  nicht mehr zusammenpassten
* Fix: Datum in der Seitenstatistik las die nicht vorhandene Spalte `tl_page.date`
  und blieb deshalb leer — jetzt wird `tstamp` verwendet
* Fix: `CounterCheck` im Standardzähler las `$GLOBALS['fhcounter']['tl_default']`
  (Schreibfehler) und war deshalb immer leer
* Fix: Durchschnitt je Tag lieferte bei Zählern unter einem Tag Alter absurd
  hohe Werte
* Fix: Diagramme im Frontend blieben leer — die eingebundene Javascript-Bibliothek
  „flot“ wurde über Contao-3-Pfade geladen, die seit Contao 4 ins Leere zeigen
* Change: Contao-5-Tauglichkeit — `TL_MODE`, `REQUEST_TOKEN`, `System::log()`,
  `ampersand()`, `Environment::getInstance()` und die globalen Klassenaliase
  ersetzt; Backend-Benutzer werden über den TokenChecker erkannt
* Change: Die Zähllogik lag wortgleich im Zählermodul und in der Insert-Tag-Klasse.
  Sie steht jetzt einmal in `Helper\Zaehlwerk`; die Aufbereitung der
  Template-Variablen (vorher achtfach kopiert) in `Helper\Auswertung`
* Change: Deutlich weniger Datenbankabfragen — Verbundindex über `source` und `pid`,
  gezielte Spaltenauswahl statt `SELECT *`, keine Abfrage mehr für abgeschaltete
  Inhaltsarten, Artikelsuche nur bei vorhandenem URL-Parameter, und in den
  Statistiken eine Abfrage für die Bezeichnungen statt einer je gezähltem Datensatz
* Change: Wiederkehrer innerhalb der Sperrzeit lösen kein Schreiben des großen
  Zählerfelds mehr aus
* Change: Statistiken werden im laufenden Zeitraum eine Stunde, in abgeschlossenen
  Zeiträumen einen Tag zwischengespeichert
* Change: Backend-Statistiken im Stil des Wertungsportal-Bundles, eigene
  `backend.css`, die nur noch im Backend geladen wird
* Change: `tl_fh_counter` und `tl_module` als UTF-8 gespeichert (die Kommentare
  waren unlesbar), Datenbankfelder `source` und `fhc_template` auf 64 Zeichen
* Remove: Abhängigkeit von `schachbulle/contao-helper-bundle`. Es wurde nur für
  den Zwischenspeicher der Statistiken gebraucht, ist aber an Contao 4 gebunden
  und hätte Contao 5 verhindert. An seine Stelle tritt Symfonys Dateispeicher,
  den beide Contao-Fassungen mitbringen. Der Zwischenspeicher liegt jetzt unter
  `var/cache` und wird beim Leeren des Contao-Caches mit geleert
* Remove: `_instanceof`-Block aus der `services.yml` — er verwies auf
  `ContainerAwareInterface`, das es seit Symfony 7 nicht mehr gibt, und
  verhinderte dadurch den Containerbau unter Contao 5. Keine Klasse dieser
  Erweiterung setzte die dort genannten Schnittstellen um
* Remove: `public/statistik.php` — band Contao über `system/initialize.php` ein
  und war seit Contao 4.5 nicht mehr lauffähig
* Remove: Javascript-Bibliothek `flot` samt jQuery-Abhängigkeit
* Remove: Tote Templates `mod_fhcounter` und `fhcounterdetails_full` sowie die
  Sprachdateien der nie existierenden Tabelle `tl_fh-counter`
* Remove: Bilder `plus.png` und `minus.png` — die Vor- und Zurückschritte der
  neuen Kopfnavigation kommen ohne Grafiken aus
* Remove: Moduleinstellung „Session-Cookie benutzen“ (`fhc_register_sessions`) —
  sie wurde nie ausgewertet

## Version 1.2.9 (2026-07-30)

* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 1.2.8 (2025-09-12)

* Fix: Warning: Undefined array key \"111.48.114.208\" at src/Classes/Register.php:252
* Fix: Warning: Undefined array key 12 at src/Classes/Register.php:267

## Version 1.2.7 (2025-09-11)

* Fix: Warning: Undefined array key "" in src/Modules/StatistikNews.php (line 173) 
* Fix: Warning: Undefined array key 9 in /src/Modules/StatistikNews.php (line 237) 

## Version 1.2.6 (2025-07-17)

* Fix: Warning: Undefined variable $cacheResult in src/Modules/StatistikNews.php (line 148) 
* Fix: Warning: Undefined array key 2025 in src/Modules/StatistikNews.php (line 233) 
* Fix: Warning: Undefined variable $cacheDatum in src/Modules/StatistikNews.php (line 215) 
* Change: Standardcachezeit von 1 Jahr auf 1 Tag gesetzt

## Version 1.2.5 (2025-02-07)

* Fix: Warning: Undefined array key 2024 in src/Modules/StatistikPages.php (line 240) 
* Fix: Frontend-Klasse Zeile 93 -> isset ergänzt

## Version 1.2.4 (2024-12-06)

* Fix: Warning: Undefined array key "counter_donotlog404" in Classes/Register.php (line 104) 

## Version 1.2.3 (2024-04-18)

* Add: tl_settings.counter_donotlog404 -> Fehler 404 nicht im System-Log protokollieren
* Add: tl_settings.counter_donotlogid -> Fehlende source_id nicht im System-Log protokollieren

## Version 1.2.2 (2022-11-11)

* Fix: Warning in PHP 8: Undefined variable $zaehlen in Classes/Register.php
* Fix: Warning in PHP 8: Attempt to read property "id" on null in Classes/Register.php
* Fix: Warning in PHP 8: Undefined variable $cacheResult in Modules/StatistikPages.php (line 148)
* Fix: Warning in PHP 8: Undefined array key "hits" in Modules/StatistikPages.php (line 284) 
* Fix: Warning in PHP 8: Undefined variable $cacheDatum in Modules/StatistikPages.php (line 221) 

## Version 1.2.1 (2022-11-11)

* Change: Abhängigkeit PHP-Version aufgehoben

## Version 1.2.0 (2022-02-15)

* Change: Modules\Statistik.php -> Modules\StatistikNews.php
* Add: Backend-Ausgabe der Seiten-Statistik
* Add: Einstellungen für Anzahl der anzuzeigenden Top-x bei Nachrichten und Seiten (Standard: 100)

## Version 1.1.5 (2021-12-20)

* Add: Ausgabe der Cachezeit (scheint nicht zu funktionieren, da der Zeitstempel nicht von retrieve "verloren geht")

## Version 1.1.4 (2021-12-16)

* Change: Caching auch für aktuellen Tag eingestellt (auf 1 Stunde)

## Version 1.1.3 (2021-12-16)

* Fix: Call to a member function store() on null (Helper-Klasse nicht richtig eingebunden)

## Version 1.1.2 (2021-12-16)

* Fix: Zeilenumbruch beim Datum und beim Archiv verhindern verhindern (Statistik-Modul)
* Add: schachbulle/contao-helper-bundle für die Cache-Funktion

## Version 1.1.1 (2021-12-15)

* Change: Umstrukturierung der Top-Tabelle, z.B. ohne Alias
* Fix: Tageszähler arbeitete falsch - hat die Monate und Jahre addiert

## Version 1.1.0 (2021-12-15)

* Add: Backend-Ausgabe der Nachrichten-Statistik

## Version 1.0.4 (2021-10-07)

* Fix: Debug-Ausgabe in Tag.php entfernt

## Version 1.0.3 (2021-10-05)

* Fix: Tag.php Abhängigkeit tl_session entfernt

## Version 1.0.2 (2020-10-22)

* Fix: Debug-Ausgabe entfernt

## Version 1.0.1 (2020-10-22)

* Fix: Leere Referer bei 404-Fehlern nicht mitloggen
* Fix: Umstellung von HTTP_REFERER (da dort nichts Aussagekräftiges drinsteht) auf REQUEST_URI
* Change: Ausgabe in tl_log erweitert auf REMOTE_ADDR und HTTP_USER_AGENT

## Version 1.0.0 (2020-10-21)

* Add: Eintrag in tl_log, wenn 404-Seite aufgerufen wurde
* Fix: Umstellung von tl_session (nicht mehr unterstützt ab Contao 4.x) auf direkte Abfrage der BackendUser-Klasse

## Version 0.0.3 (2020-10-11)

* Fix: Register.php, Input-Klasse Contao falsch angesprochen

## Version 0.0.2 (2020-05-21)

* Fix .gitignore

## Version 0.0.1 (2020-05-21)

* Migration der C3-Version 1.1.3 nach C4
