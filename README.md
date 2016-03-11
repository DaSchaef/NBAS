# NBAS
NBAS - NAMI Bezirks API Sammlung, Eine kleine Sammlung des Codes, den wir im DPSG Bezirk Kurpfalz benutzen, um unsere Emailverteiler anhand der NAMI Daten zu setzen.

# Lizenz
Ich habe den Code unter GPL gestellt in dem Gedanke, dass wir DPSGler miteinander teilen sollen. Hier möchte ich euch auch noch einmal auf http://ncm.dpsg.de hinweisen,
auch wenn es dort nicht sehr aktiv aussehen mag, dort lesen einige Entwickler mit. Also traut euch!

# Danksagung
Ein großes Dankeschön an Daniel von der der AG NCM und an fabianlipp für sein tolles fabianlipp/jnami.

# Features
NBAS ist eine kleine Scriptsammlung, die aus NAMI die Emailadressen der Leiter unseres Bezirks extrahieren und in Mailman einspielen soll.
Im jetzigen Zustand ist NBAS dafür ausgelegt von der Konsole aus ausgeführt zu werden.
Dadurch müssen die Stammesvorstände nur noch ein Datensatz verwalten und können trotzdem sicher sein, dass auch die Bezirks-Emails bei der Leiterrunde ankommen.

Die NBAS Mailman Schnittstelle hält im TMP Verzeichnis die Mailman-Mitglieder unter GIT Verwaltung.
Dies is ein Sicherheits-Feature, damit notfalls schnell Änderungen rückgängig gemacht werden können.

Zur Zeit befindet sich NBAS noch in der Entwicklung.
Die NAMI API Abfrage klappt, die Mailman-Schnittstelle rudimentär funktionsfähig.

# Datenschutz
Bitte beachtet, dass die temporären Dateien im tmp Verzeichnis unter GIT Kontrolle gehalten werden.
Aus Gründen des Datenschutzes solltet ihr das Verzeichnis regelmäßig leeren (nicht löschen!) und dabei auch
das GIT Repository (.git Ordner) löschen.
NBAS legt automatisch dann beim nächsten Mal ein GIT Repository wieder an.

# Requirements / Voraussetzungen
Für NBAS wird benötigt
- git
- mailman
- php
- php-curl
- Ein API Zugang (siehe http://ncm.dpsg.de)
- Schreibrecht im Ordner ./tmp (für Mailman und NAMI cookies)

# Installation
- In Zielverzeichnis gehen
- Download NBAS:
`git clone https://github.com/DaSchaef/NBAS.git`
- config.php.template kopieren nach config.php
- In config.php die notwendigen Einstellungen/Zugangsdaten setzen
- NBAS ausführen mit:
`php index.php`
- Bitte beachte, dass das TMP Verzeichnis .tmp beschreibbar sein muss für Session-Cookies und die Mailman Dateien.

# Einstellungen
- `$config["namiserver"]`
URL unter der NAMI erreichbar ist

- `$config["apiuser_mitgliedsnummer"]`
Die Mietgliednummer des Nutzers der API Rechte hat

- `$config["apiuser_password"]`
Das entsprechende Passwort des Nutzers

- `$config["tmpdir"]`
Das temporäre Verzeichnis muss beschreibbar sein.
NBAS speichert dort die NAMI Session Cookies und verwatet dort (unter GIT Kontrolle) die Mitgliederlisten der Mailing-Listen als Textdatei.

- `$config["mailliste_additional_dir"]`
In diesem Verzeichnis sucht NBAS nach einer Textdatei mit dem Namen der Mailingliste. In dieser Textdatei stehen Emailadresse. Pro Zeile eine Emailadresse. Ist eine solche Textdatei vorhanden, liest NBAS diese Zeile für Zeile ein und versucht die Emailadressen zur Mailingliste (definiert durch den Dateinamen) hinzuzufügen.

- `$config["mailliste_mapping"]`
Hier wird das Mapping von NAMI Einstellungen zu Mailman-Liste gemacht.
Ein Beispiel ist in der config.php.template zu finden.
