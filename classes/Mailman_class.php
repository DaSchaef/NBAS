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

include_once 'MailmanList_class.php';

/** Klasse, welche die Informationen einer Mailing-Liste enthält*/
class Mailman_class {

    /** Beim Konstruktor wird gleich geprüft, ob alle notwendigen Befehle da sin*/
    function Mailman_class() {
        if(!$this->command_exist("./list_members")) {
            throw new RuntimeException("Kann kein Programm zu list_members findens");
        }

        if(!$this->command_exist("./add_members")) {
            throw new RuntimeException("Kann kein Programm zu add_members findens");
        }

    }

    /** Erzeugt ein MailmanList Objekt, das im Prinzip ein Array aus Emails ist (mit Prüfungen)
        @param name Name der Liste
        @return MailmanList_class Object
    */
    function getList($name) {
        $type_value = gettype($name);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        if(preg_match("/^[a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $name) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
            throw new RuntimeException("Kein gültiger Listennamen, kann aus Sicherheitsgründen nicht hier angegeben werden");
        }
        $returncode = 1;
        exec("./list_members " . $name, $list_members, $returncode);

        if($returncode !== 0) {
            throw new RuntimeException("List Members Return Code ist nicht 0: " . $returncode);
        }
        $liste = new MailmanList_class($name, $list_members);
        return $liste;
    }

    /** Löscht alle Teilnehmer aus einer Liste
        @param listname Name der Liste
    */
    function emptyList($listname) {
        $type_value = gettype($listname);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        if(preg_match("/^[a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $listname) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
            throw new RuntimeException("Kein gültiger Listennamen, kann aus Sicherheitsgründen nicht hier angegeben werden");
        }
    }

    /** Aktualisiert die Liste mit neuen Einträgen
        @param newentries_array Neue Einträge als array mit emails als elemente
        @param listname die zu aktualisierende Liste*/
    function updateList($newentries_array, $listname) {
        $type_value = gettype($newentries_array);
        if($type_value !== "array") {
            throw new RuntimeException("1. Argument muss Array sein " . $type_value);
        }

        $type_value = gettype($listname);
        if($type_value !== "string") {
            throw new RuntimeException("2. Argument muss String sein " . $type_value);
        }
        $list = $this->getList($listname);

        if(!$list->checkConditions()) { // Es gab Warnungen
            echo("Warnungen in updateList() - Before\n");
            echo($list->message);
        }

        $this->emptyList($listname);

        $list->replaceMembers($newentries_array);

        if(!$list->checkConditions()) { // Es gab Warnungen
            echo("Warnungen in updateList() - After\n");
            echo($list->message);
        }

        foreach ($list->members as $key => $email) {
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("Keine gültige Email: " . $email);
            }
            exec("./add_members " . $list->name . " " . $email, $added, $returncode);
            if($returncode !== 0) {
                echo("Warnungen in addmembers\n");
                echo("Add Members Return Code ist nicht 0: " . $returncode . " " . $email);
            }
            //@TODO Check Ausgabe
        }
    }

    /** Interne Helper Funktion, die prüft ob ein Kommando auf dem Server verfügbar ist.
        @param $cmd: Kommando Name
        @return true oder false je nach dem ob Kommando verfügbar ist*/
    protected function command_exist($cmd) {
        if(preg_match("/^[\.\/a-zA-Z][a-zA-Z0-9\.-_]*$/mi", $cmd) !== 1) { // Muss mit Buchstaben anfangen. Prüft ob einzelnes Wort, nur Buchstaben, Zahlenund -_. erlaubt
            throw new RuntimeException("Kein gültiger Command-Name, kann aus Sicherheitsgründen nicht hier angegeben werden");
        }

        $returnVal = shell_exec("which $cmd");
        return (empty($returnVal) ? false : true);
    }

}
?>