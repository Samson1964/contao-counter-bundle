# Counter

Eine Erweiterung für **Contao 4.13 und Contao 5**, die die Zugriffe auf Seiten, Artikel und
Nachrichten zählt, die Zahlen im Frontend ausgibt und sie im Backend als Bestenliste mit
Verlaufsdiagramm auswertet. Auf Wunsch verschickt ein täglicher Cronjob die Bestenlisten
per E-Mail.

Die Erweiterung ist auf schachbund.de seit Jahren im produktiven Einsatz.

## Installation

```bash
composer require schachbulle/contao-counter-bundle
```

Anschließend den Datenbankabgleich ausführen (Contao-Manager oder `contao:migrate`).

## Zählung einrichten

Counter besteht aus einem **Zählermodul** und einem **Ausgabemodul**. Beide finden Sie unter
*Themes → Module* im Bereich **Counter**.

Das Zählermodul zählt und gibt selbst nichts aus. Es muss **vor** dem Ausgabemodul im
Seitenlayout stehen und wird nur einmal eingebunden. Das Ausgabemodul kann an beliebig
vielen Stellen mit unterschiedlichen Templates stehen.

Wer ohne Module auskommen möchte, nimmt stattdessen die Insert-Tags `{{fhcounter}}` (zählt)
und `{{fhcounterview}}` (gibt aus). Sie arbeiten mit festen Vorgaben und kennen keine
Moduleinstellungen.

Die Zählstände liegen in der Tabelle `tl_fh_counter`; die Werte des aktuellen Aufrufs stehen
zusätzlich in `$GLOBALS['fhcounter']`, wo das Ausgabemodul sie abholt.

## Statistiken im Backend

In der **Seitenstruktur**, bei den **Artikeln** und bei den **Nachrichten** führt jeweils der
Knopf *Statistik* zur Auswertung. Sie zeigt:

* die Bestenliste der meistbesuchten Inhalte im gewählten Zeitraum,
* ein Verlaufsdiagramm über alle Inhalte zusammen,
* eine Navigation über Tag, Monat und Jahr mit Vor- und Zurückschritt.

Das Diagramm zeigt jeweils die nächstfeinere Einteilung: die 24 Stunden eines Tages, die Tage
eines Monats oder die zwölf Monate eines Jahres. Es entsteht als SVG auf dem Server und
braucht kein Javascript.

Der Knopf **per E-Mail versenden** schickt genau den angezeigten Zeitraum an die eingestellten
Empfänger. Vor dem Abschicken erscheinen die Adressen und lassen sich für diesen einen Versand
ändern — die dauerhaften Empfänger bleiben davon unberührt. Weil die Auswertung zu diesem
Zeitpunkt bereits errechnet ist, geht die Mail sofort hinaus.

Die Auswertung wird zwischengespeichert — der laufende Zeitraum eine Stunde, abgeschlossene
Zeiträume einen Tag.

## Statistik per E-Mail

Unter *System → Einstellungen → Zähler: Statistik per E-Mail* lässt sich ein täglicher Versand
einschalten. Verschickt wird:

* immer die Bestenliste des **Vortags**,
* montags zusätzlich die der **vergangenen Woche** (Montag bis Sonntag),
* am Monatsersten zusätzlich die des **Vormonats**.

Je Inhaltsart geht eine eigene E-Mail an einen **eigenen Verteiler** — wer die Seitenstatistik
bekommt, ist selten dieselbe Runde wie beim Nachrichten-Ranking. Empfänger und Kopieempfänger
werden deshalb für Seiten, Artikel und Nachrichten getrennt gepflegt; Absender, Betreffzusatz,
Vorlage und die Zahl der Listenplätze gelten für alle drei gemeinsam.

**Eine Inhaltsart ohne Empfänger wird übersprungen.** Damit ist zugleich gesagt, welche
Statistiken überhaupt hinausgehen: Wer nur die Nachrichtenstatistik braucht, trägt allein dort
Adressen ein und lässt die übrigen Felder leer.

Das Layout steckt im Template `counter_mail_standard`. Eine eigene Fassung legen Sie unter
`templates/` mit dem Namensanfang `counter_mail_` ab; sie erscheint dann in der Auswahlliste.
Die Zeilenfarbe zeigt das Alter des Inhalts an, von Grün (ganz aktuell) über Gelb bis Rot und
Grau (älter als ein Jahr).

Voraussetzung ist ein eingerichteter Contao-Cronjob (Contao-Manager oder ein Aufruf von
`contao:cron` durch den Hoster).

Die Zeiträume werden dabei **vom seltensten zum häufigsten** abgearbeitet: erst der Vormonat,
dann die Vorwoche, zuletzt der Vortag. Jeder Zeitraum kostet einen Durchlauf durch die gesamte
Zählertabelle; schneidet ein PHP-Zeitlimit den Lauf ab, fällt so die Tagesstatistik aus — die
morgen wiederkommt — statt der Monatsstatistik, die dann für immer fehlte. Wer ganz sicher
gehen will, ruft `contao:cron` über die Konsole auf; dort gibt es kein Zeitlimit. Und was
trotzdem einmal liegen bleibt, lässt sich über den Versandknopf in der Statistik nachholen.

## Weitere Einstellungen

Unter *System → Einstellungen → Zähler*:

| Einstellung | Bedeutung |
|---|---|
| Anzahl Seiten / Artikel / Nachrichten | Länge der Bestenliste im Backend |
| Fehler 404 nicht protokollieren | Aufrufe nicht vorhandener Seiten nicht ins Systemprotokoll schreiben |
| Fehlende Quell-ID nicht protokollieren | Nicht vermerken, wenn ein Inhalt nicht zugeordnet werden konnte |

Am Zählermodul selbst werden Onlinezeit (wie lange ein Besucher als „online“ gilt) und
Zählsperre (wie lange ein wiederkehrender Besucher nicht erneut zählt) eingestellt, ebenso ob
angemeldete Backend-Benutzer mitgezählt werden.

## Template-Variablen des Ausgabemoduls

Mitgeliefert werden die Templates `fhcounter_mini`, `fhcounter_standard`, `fhcounter_full` und
`fhcounter_diagramme`. Eigene Fassungen unter `templates/` mit dem Namensanfang `fhcounter_`
erscheinen in der Auswahlliste.

| Variable | Inhalt |
|---|---|
| `ViewCounterinfo` | Kopfdaten des Zählers vorhanden ja/nein |
| `ViewDiagrams` | Diagramme vorhanden ja/nein |
| `CounterSource` | Name der Quelltabelle (tl_page, tl_article, tl_news) |
| `CounterPid` | ID des Inhalts in seiner Quelltabelle |
| `CounterStarttime` | Zeitstempel der ersten Zählung |
| `CounterLastcounting` | Zeitstempel der letzten Zählung |
| `CounterLastip` | IP-Adresse des letzten Besuchers |
| `CounterOnline` | Zahl der aktuellen Besucher dieser Adresse |
| `CounterTopOnlineCount` | Bestmarke der gleichzeitigen Besucher |
| `CounterTopOnlineTime` | Zeitstempel dieser Bestmarke |
| `CounterAll` / `CounterTotalhits` | Gesamtzugriffe |
| `CounterThisYear` / `CounterThisMonth` / `CounterThisDay` / `CounterThisHour` | Zugriffe im laufenden Jahr, Monat, Tag, in der laufenden Stunde |
| `CounterYesterday` / `CounterLastMonth` | Zugriffe gestern bzw. im Vormonat |
| `CounterAverage` | durchschnittliche Zugriffe je Tag |
| `CounterCheck` | wurde dieser Besucher gezählt? |
| `CounterHoursChart` / `CounterDaysChart` / `CounterMonthsChart` | fertige SVG-Diagramme der letzten 24 Stunden, 30 Tage, 12 Monate |

Den Namen kann ein Präfix vorangestellt werden: **Page**, **Article** oder **News**.
`PageCounterAverage` liefert also die durchschnittliche Besucherzahl der aktiven Seite, egal
ob gerade ein Artikel oder eine Nachricht angezeigt wird.

Der Zähler ohne Präfix ist der **Standardzähler**. Er wird in der Reihenfolge Seite, Artikel,
Nachricht befüllt — es gewinnt also der speziellste Inhalt, der auf der Seite angezeigt wird.

## Voraussetzungen an die Contao-Einstellungen

Counter arbeitet mit folgenden Frontend-Einstellungen einwandfrei:

* URLs umschreiben = ja
* Auto_item aktivieren = ja
* Die Sprache zur URL hinzufügen = nein
* Leere URLs nicht umleiten = nein
* Ordner-URLs verwenden = nein
* Keine Seitenaliase verwenden = nein

Andere Kombinationen sind nicht durchgetestet. Solange nur Seiten gezählt werden, ist Counter
unempfindlich — die Seiten-ID bekommt ein Modul von Contao geliefert.

Bei Artikeln liest der Zähler den URL-Parameter `articles` aus. Bei Nachrichten ist es
aufwendiger, weil Contao einem Modul nicht verrät, welche Nachricht der Nachrichtenleser
gerade zeigt: Der Zähler sammelt die Weiterleitungsseiten aller Nachrichtenarchive und
schneidet, wenn die aktuelle Seite eine davon ist, den Alias aus der Adresse.

## Fehler und Unterstützung

Fragen und Fehlermeldungen gern über die
[GitHub-Issues](https://github.com/Samson1964/contao-counter-bundle/issues) oder im
Contao-Forum (Samson1964).

**Frank Hoppe**
