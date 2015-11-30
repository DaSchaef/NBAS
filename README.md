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

Zur Zeit befindet sich NBAS noch in der Entwicklung.
Die NAMI API Abfrage klappt, die Mailman-Schnittstelle ist noch nicht funktionsfähig.

# Requirements / Voraussetzungen
Für NBAS wird benötigt
- php
- php-curl
- Ein API Zugang (siehe http://ncm.dpsg.de)
- Schreibrechte auf die Datei ./nami_curl_cookies.txt für die Cookies

# Installation
- In Zielverzeichnis gehen
- Download NBAS:
`git clone https://github.com/DaSchaef/NBAS.git`
- config.php.template kopieren nach config.php
- In config.php die notwendigen Einstellungen/Zugangsdaten setzen
- NBAS ausführen mit: 
`php index.php`
- Bitte beachte, dass im Arbeitsverzeichnis eine Datei nami_curl_cookies.txt von NBAS erzeugbar oder beschreibbar sein muss. Dies ist notwendig für die Session-Cookies.
