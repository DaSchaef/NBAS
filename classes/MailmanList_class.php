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

/** Klasse, welche die Informationen einer Mailing-Liste enthält*/
class MailmanList_class {
    const TMP_PATH = "./tmp/";

    var $members = array(); /// Array mit Emails
    var $name = ""; /// Name der Liste

    var $warning = false; /// Ist eine Warnung aufgetreten
    var $message = ""; /// Meldungen, die man nach außen melden kann (z.B. Warnungen)
    var $error = false; /// Ist ein Fehler aufgetreten
    var $deviation_percent_warning = 30; /// Angabe der maximalen prozentualen Abwecihung beim updaten der Liste. Darüber gibt es eine Warnung
    var $deviation_percent_error = 60; /// Angabe der maximalen prozentualen Abwecihung beim updaten der Liste. Darüber gibt es eine Warnung

    /** Konstruktor
        @param $name Name der Liste
        @param string_array Array mit Emails als String-Array
    */
    function MailmanList_class($name, $string_array) {
        if(!is_writable(self::TMP_PATH)) {
            throw new RuntimeException("\nKann im TMP Verzeichnis keine Datei anlegen.\n");
        }

        $type_value = gettype($name);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        $type_value = gettype($string_array);
        if($type_value !== "array") {
            throw new RuntimeException("2. Argument muss Array sein " . $type_value);
        }

        $this->name = $name;

        foreach($string_array as $key => $line){ // Iterate over each line an search emails
           if(filter_var($line, FILTER_VALIDATE_EMAIL)) {
               if(in_array($line, $this->members, true)) {
                   $this->warning = true;
                   $this->message = $this->message . "Email ist bereits in Liste? Doppelter Eintrag!? " . $line . "\n";
               } else {
               		array_push($this->members, $line);
               }
           }
        }
    }

    /** Prüft, ob alles ok ist, bei Warnung gibt es falls, bei fehler gibt es eine Exception
        @return true wenn alles ok ist */
    public function checkConditions() {
        if( $this->warning) {
            return false;
        }

        if( $this->error) {
            throw new RuntimeException("Es lag bereits ein Fehler vor");
        }

        if(sizeof($this->members) <= 0) {
            throw new RuntimeException("Keine Members!");
        }
        return true;
    }

    /** Löscht den Inhalt einer Liste */
    public function emptyMembers() {
      $this->warning = false;
      $this->error = false;
      $this->members = array();
    }

    /** Ersetzt die Emails einer Liste.
        @param members_new Array mit Email-Strings
    */
    public function replaceMembers($members_new) {
        $type_value = gettype($members_new);
        if($type_value !== "array") {
            throw new RuntimeException("Argument muss Array sein " . $type_value);
        }
        $members_size = count($this->members);
        $members_new_size = count($members_new);

        $abweichung = $members_size/100.0*abs($members_size-$members_new_size);
        if($abweichung >= $this->deviation_percent_error) {
            $this->error = true;
            throw new RuntimeException("Abbruch, es würden " . $abweichung . "% geändert");
        }
        if($abweichung >= $this->deviation_percent_warning) {
            $this->warning = true;
            $this->message = $this->message . "Achtung, es werden " . $abweichung . "% geändert" . "\n";
        }

        $members = array();
        foreach($members_new as $key => $line){ // Iterate over each line an search emails
           if(filter_var($line, FILTER_VALIDATE_EMAIL)) {
               if(in_array($line, $this->members, true)) {
                   $this->warning = true;
                   $this->message = $this->message . "Email ist bereits in Liste? Doppelter Eintrag!? " . $line . "\n";
               } else {
                   array_push($this->members, $line);
               }
           }
        }
   }

   /** Schreibt die Mitlieder einer Liste in eine Datei, damit sie von Mailman_class mittels add_members
       geschrieben werden kann */
   public function write() {
     if(preg_match("/^[\.\/a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $this->name) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
         print_r(preg_match("/^[\.\/a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $this->name));
         throw new RuntimeException("Kein gültiger Listen-Namen, kann aus Sicherheitsgründen nicht hier angegeben werden");
     }

     if(!file_exists(self::TMP_PATH . $this->name) &&!is_writable(self::TMP_PATH )) {
         throw new RuntimeException("\nKann " . self::TMP_PATH . " nicht beschreiben\n");
     }

     if(file_exists(self::TMP_PATH . $this->name) &&!is_writable(self::TMP_PATH . $this->name)) {
         throw new RuntimeException("\nKann " . self::TMP_PATH . $this->name . " nicht beschreiben\n");
     }

     // Wenn vorhanden, dann setze Datei unter Verionskontrolle, damit Script nicht Ammok laufen ldap_connect
     // und Admin blöd da steht ;-)
     if(file_exists(self::TMP_PATH . $this->name)) {
       $git = new GitHandler_class(self::TMP_PATH);
       $git->revision($this->name);
     }

     $fp = fopen(self::TMP_PATH . $this->name, 'w');
     // Schreibe Mail in jeweils eine Zeile
     foreach ($this->members as $key => $email) {
         if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
             throw new RuntimeException("Keine gültige Email: " . $email);
         }
         fwrite($fp, $email . "\n");
     }
     fclose($fp);
  }

}
?>
