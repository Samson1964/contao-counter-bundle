# Counter Changelog

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
