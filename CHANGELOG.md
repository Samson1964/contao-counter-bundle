# Counter Changelog

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
