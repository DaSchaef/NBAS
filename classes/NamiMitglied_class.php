<?php
/** NBA - NAMI Bezirks API, Eine kleine Sammlung des Codes,
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

/** Klasse, welche die für uns wichtige Eigenschaften enthält*/
class NamiMitglied_class {

    /// Felder Array die gespeichert werden sollen. Der Array ist zweigeteilt. "entries" enthält die eigentlichen Werte. "types" enthält die Typen, für Error Check.
    public $fields = array("entries" => array(
                                    "nachname" => "",
                                    "vorname" => "",
                                    "status" => "",
                                    "mitgliedsNummer" => "",
                                    "gruppierung" => "",
                                    "gruppierungId" => "",
                                    "email" => ""
                                    ),
                        "types" => array(
                                    "nachname" => "String",
                                    "vorname" => "String",
                                    "status" => "String",
                                    "mitgliedsNummer" => "Unsigned Integer",
                                    "gruppierung" => "String",
                                    "gruppierungId" => "Unsigned Integer",
                                    "email" => "Email"
                                    ),
                        );


    /** Konstruktor-
        @param $json_data Json Objekt mit den Daten
    */
    function NamiMitglied_class($json_data) {
        foreach ($this->fields["entries"] as $field => $data) {
            if(!isset($json_data->$field)) {
                throw new RuntimeException("In Mitglied-JSON Objekt fehlt das Feld: " . $field);
            }
            $this->fields["entries"][$field] = $json_data->$field;
            $this->CheckType($field);
        }
    }

    /** Prüft ob ein Feld ein validen Wet enthält
        @param $index Feld Eintrag
        @return Nichts, wirft bei Fehler Exceptions*/
    function CheckType($index) {
        $type_value = gettype($index);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        $value = $this->fields["entries"][$index];
        $type = $this->fields["types"][$index];

        if(!isset($value)) {
            throw new RuntimeException("Feld " . $index . " ist nicht gesetzt");
        }

        if(!isset($type)) {
            throw new RuntimeException("Kann Typ für Feld " . $index . " nicht erkennen");
        }

        $type_value = gettype($value);

        if($type == "String") {
            if($type_value != "string") {
                throw new RuntimeException("Feld " . $index . " ist kein String: " . $type_value);
            }
        } elseif($type == "Unsigned Integer") {
            if($type_value != "integer" and !is_numeric($value)) {
                throw new RuntimeException("Feld " . $index . " ist kein Integer: " . $type_value);
            } elseif($value < 0) {
                throw new RuntimeException("Feld " . $index . " ist kleiner 0: " . $value);
            }
        } elseif($type == "Email") {
            if($type_value != "string") {
                throw new RuntimeException("Feld " . $index . " ist kein E-Mail String: " . $type_value);
            }
            elseif ($value != "" and !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Feld " . $index . " ist keine Email: " . $value);
            }
        }
    }

    /** Meta-Funktion, damit "echo()" funktioniert*/
    function __toString() {
        return $this->fields["entries"]["gruppierung"] . ": " .$this->fields["entries"]["vorname"] . " " . $this->fields["entries"]["nachname"] . ": " . $this->fields["entries"]["email"];
    }
}

?>