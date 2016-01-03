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
include_once 'GitHandler_class.php';

/** Klasse, welche die Informationen einer Mailing-Liste enthält*/
class MailmanList_class {
    protected $members = array(); /// Array mit Emails

    protected $name = ""; /// Name der Liste
    protected $git;

    /** Konstruktor
        @param $name Name der Liste
        @param string_array Array mit Emails als String-Array
    */
    function MailmanList_class($name, $tmppath = "./") {
        $this->TMP_PATH = $tmppath;

        if(!is_writable($this->TMP_PATH)) {
            throw new RuntimeException("\nKann im TMP Verzeichnis keine Datei anlegen.\n");
        }

        $type_value = gettype($name);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        if(!$this->command_exist("./list_members")) {
            throw new RuntimeException("Kann kein Programm zu list_members findens");
        }

        if(!$this->command_exist("./add_members")) {
            throw new RuntimeException("Kann kein Programm zu add_members findens");
        }

        if(preg_match("/^[\.\/a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $name) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
            throw new RuntimeException("Kein gültiger Listen-Namen, kann aus Sicherheitsgründen nicht hier angegeben werden");
        }

        $this->name = $name;
    }

    /** Löscht den Inhalt einer Liste */
    public function clear() {
        $this->warning = false;
        $this->error = false;
        $this->members = array();
    }

   /** Schreibt die Mitlieder einer Liste in eine Datei, damit sie von Mailman_class mittels add_members
       geschrieben werden kann */
   public function update() {
       if(!file_exists($this->TMP_PATH . $this->name) &&!is_writable($this->TMP_PATH)) {
           throw new RuntimeException("\nKann " . $this->TMP_PATH . " nicht beschreiben\n");
       }

       if(file_exists($this->TMP_PATH . $this->name) &&!is_writable($this->TMP_PATH . $this->name)) {
           throw new RuntimeException("\nKann " . $this->TMP_PATH . $this->name . " nicht beschreiben\n");
       }

       // Wenn vorhanden, dann setze Datei unter Verionskontrolle, damit Script nicht Ammok laufen kann
       // und Admin blöd da steht ;-)
       if(file_exists($this->TMP_PATH . $this->name)) {
         $git = new GitHandler_class($this->TMP_PATH);
         $git->revision($this->name);
       }

       $fp = fopen($this->TMP_PATH . $this->name, 'w');
       if($fp === FALSE) {
          throw new RuntimeException("\nKann " . $this->TMP_PATH . $this->name . " nicht öffnen\n");
       }

       // Schreibe Mail in jeweils eine Zeile
       foreach ($this->members as $key => $email) {
           if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
               throw new RuntimeException("Keine gültige Email: " . $email);
           }
           fwrite($fp, $email . "\n");
       }
       fclose($fp);

       /*exec("./add_members " . $list->name . " " . $email, $added, $returncode);
       if($returncode !== 0) {
           echo("Warnungen in addmembers\n");
           echo("Add Members Return Code ist nicht 0: " . $returncode . " " . $email);
       }*/
  }

  /** Liest Emails aus Liste ein
      @param name Name der Liste
      @return MailmanList_class Object
  */
 public function open() {
    $returncode = 1;
    exec("./list_members " . $this->name, $list_members, $returncode);

    if($returncode !== 0) {
        throw new RuntimeException("List Members Return Code ist nicht 0: " . $returncode);
    }
    $this->members = $list_members;
  }

 /** Funktion um anhand von Dateiname Mitglieder hinzuzufügen */
 public function import($filename) {
     $type_value = gettype($filename);
     if($type_value !== "string") {
         throw new RuntimeException("1. Argument muss String sein " . $type_value);
     }

     if(!file_exists($filename)) {
        throw new RuntimeException($filename . " existiert nicht!");
     }

     $fp = fopen($filename, 'r');
     if($fp === FALSE) {
        throw new RuntimeException("\nKann " . $filename . " nicht öffnen\n");
     }
 }

  /** Aktualisiert die Liste mit neuen Einträgen
      @param newentries_array Neue Einträge als array mit emails als elemente
      @param listname die zu aktualisierende Liste*/
  public function replace($newentries_array) {
      $type_value = gettype($newentries_array);
      if($type_value !== "array") {
          throw new RuntimeException("1. Argument muss Array sein " . $type_value);
      }
      $this->clear();
      $this->add($newentries_array);
  }

  /** Funktion, welche die Liste mit members befüllt */
  public function add($newentries_array) {
      $type_value = gettype($newentries_array);
      if($type_value !== "array") {
          throw new RuntimeException("1. Argument muss Array sein " . $type_value);
      }

      foreach($newentries_array as $key => $line){ // Iterate over each line an search emails
         if(filter_var($line, FILTER_VALIDATE_EMAIL)) {
             if(in_array($line, $this->members, true)) {
                 $this->warning = true;
                 echo("Email ist bereits in Liste? Doppelter Eintrag!? " . $line . "\n");
             } else {
                 array_push($this->members, $line);
             }
         }
      }
  }

  /** Interne Helper Funktion, die prüft ob ein Kommando auf dem Server verfügbar ist.
      @param $cmd: Kommando Name
      @return true oder false je nach dem ob Kommando verfügbar ist*/
  protected function command_exist($cmd) {
      $type_value = gettype($cmd);
      if($type_value !== "string") {
          throw new RuntimeException("1. Argument muss String sein " . $type_value);
      }

      if(preg_match("/^[\.\/a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $cmd) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
          throw new RuntimeException("Kein gültiger Command-Name, kann aus Sicherheitsgründen nicht hier angegeben werden");
      }

      $returnVal = shell_exec("which $cmd");
      return (empty($returnVal) ? false : true);
  }

}
?>
