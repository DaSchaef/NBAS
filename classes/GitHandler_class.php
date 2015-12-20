<?php
/** NBAS - NAMI Bezirks API Sammlung, Eine kleine Sammlung des Codes,
    den wir im DPSG Bezirk Kurpfalz benutzen, um unsere Emailverteiler anhand der NAMI Daten zu setzen.

    Bitte gebt eure eigenen Anpassungen und Änderungen an die Community zurück!
    Ein guter Anlaufpunkt ist http://ncm.dpsg.de

    Copyright (C) 2015  Daniel Schäfer / DPSG Ketsch / DPSG Kurpfalz

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/** Klasse, welche etwas GIT abstrahiert*/
class GitHandler_class {
    const GIT_BIN = "/usr/bin/git";
    protected $repository_path = "";

    /** Beim Konstruktor wird gleich geprüft, ob alle notwendigen Befehle da sind */
    function GitHandler_class($path) {
        if(preg_match("/^[a-zA-Z0-9\.-_\/]*$/mi", $path) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
            throw new RuntimeException("Kein gültiger Verzeichnis-Name, kann aus Sicherheitsgründen nicht hier angegeben werden");
        }
        if(!$this->command_exist(self::GIT_BIN)) {
            throw new RuntimeException("Kann kein Programm zu " . self::GIT_BIN . " finden");
        }

        $this->repository_path = $path;

        if(!$this->is_repository()) {
           $this->create_repository();
        }
    }

    /** Hält eine Datei unter Revision Control
        @param file Dateiname, entweder absolut oder relativ zu repository_path
    */
    public function revision($file) {
      if(file_exists($this->repository_path . $file)) {
        exec("cd " . $this->repository_path . " && git add " . $file, $not_used, $returncode);
        if($returncode === 0) {
          exec("cd " . $this->repository_path . " && git commit -a -m\"" . $file . "\"", $not_used, $returncode);
          if($returncode === 0 || $returncode === 1) { // 0 = commit, 1 = nothing to comit
            return true;
          } else {
            throw new RuntimeException("\nKann nicht zu GIT commiten, Problem mit GIT\n");
          }
        } else {
          throw new RuntimeException("\nKann nicht zu GIT hinzufügen, Problem mit GIT\n");
        }
      } else {
        throw new RuntimeException("\nKann nicht revisionieren, Problem mit Datei\n");
      }
    }

    /** Prüft anhand von ReturnCode von Git, ob das Verzeichnis unter GIT Kontrolle steht
        @return true wenn es ein GIT Repository gibt
    */
   public function is_repository() {
     exec("cd " . $this->repository_path . " && git ls-files . --error-unmatch 2> /dev/null", $not_used, $returncode);
     if($returncode === 0) {
       return true;
     } else {
       return false;
     }
   }

   /** Erzeugt ein neues GIT Repository */
   public function create_repository() {
     exec("git init " . $this->repository_path, $not_used, $returncode);
     if($returncode === 0) {
       return true;
     } else {
       throw new RuntimeException("\nGIT kann kein Repository anlegen.\n");
     }
   }

    /** Interne Helper Funktion, die prüft ob ein Kommando auf dem Server verfügbar ist.
        @param $cmd: Kommando Name
        @return true oder false je nach dem ob Kommando verfügbar ist
    */
    protected function command_exist($cmd) {
        if(preg_match("/^[\.\/a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $cmd) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
            throw new RuntimeException("Kein gültiger Command-Name, kann aus Sicherheitsgründen nicht hier angegeben werden");
        }

        $returnVal = shell_exec("which $cmd");
        return (empty($returnVal) ? false : true);
    }

}
?>
