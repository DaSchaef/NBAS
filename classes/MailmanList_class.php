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
    function checkConditions() {
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

    /** Ersetzt die Emails einer Liste.
        @param members_new Array mit Email-Strings
    */
    function replaceMembers($members_new) {
        $type_value = gettype($members_new);
        if($type_value !== "array") {
            throw new RuntimeException("Argument muss Array sein " . $type_value);
        }
        $members_size = count($this->members);
        $members_new_size = count($members_new);

        $abweichung = $members_size/100.0*abs($members_size-$members_new_size);
        if($abweichung >= $this->deviation_percent_warning) {
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

}
?>