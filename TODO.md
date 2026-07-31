# Counter

## Offene Aufgaben

* Livetest auf schachbund.de: Nach dem Einspielen von 2.0.0 prüfen, ob die
  Zählstände weiterlaufen und die drei Backend-Statistiken die gewohnten Zahlen
  zeigen. Besonders die Nachrichtenzählung, weil sie den Alias aus der Adresse
  schneidet.
* Mailversand scharf schalten: Die Adressen der alten Skripte (Präsident, Presse,
  Webmaster) stehen jetzt unter System → Einstellungen → Zähler und müssen dort
  einmal eingetragen werden. Bis dahin verschickt der Cronjob nichts.
* Danach den alten Hoster-Cronjob auf `web/php/pagescount.php`,
  `articlescount.php` und `newscount.php` abschalten und die drei Dateien auf
  dem Server löschen — der Contao-Cronjob übernimmt.
* Die Nachrichtenzählung hängt am Adressaufbau (Alias direkt hinter dem
  Seitenalias). Sauberer wäre der Weg über den `getPageLayout`- oder
  `parseArticles`-Hook, das ändert aber die Zuordnung bestehender Zählstände —
  nur zusammen mit einer Umstellung angehen.

## Erledigte Aufgaben

* **2026-07-31, Fassung 2.0.0**
  * Anpassung an PHP 8, Contao 4.13 und Contao 5
  * SQL-Abfragen optimiert (Verbundindex, gezielte Spalten, keine N+1-Abfragen
    in den Statistiken, kein Schreiben des Zählerfelds bei Wiederkehrern)
  * Design der Statistiken überarbeitet, Kopfnavigation und Diagramme ergänzt
  * Statistik für Artikel ergänzt
  * Die drei externen Skripte aus `aus_web/` als täglicher Contao-Cronjob
    integriert, Adressen unter System → Einstellungen pflegbar
  * Statistik-Mails mit neuem Layout und wählbarer Mailvorlage
  * `public/statistik.php` und die flot-Bibliothek entfernt; die Diagramme
    entstehen jetzt serverseitig als SVG
