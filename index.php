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
include_once 'classes/NamiConnection_class.php';
include_once 'classes/Mailman_class.php';

if(file_exists('config.php')) {
    include 'config.php';
}
else {
	throw new Exception("Achtung! Es muss eine config.php existieren. Ihr könnt euch die config.php.tempplate kopieren und anpassen.", 1);
}

$nami = new NamiConnection_class($config);

echo("Start Auth\n");
$nami->auth();
$mitglieder_email_array = $nami->listMitgliederEmailArray(true, NamiConnection_class::$FIELD_ROVER);
echo("Done\n");

$mailman = new Mailman_class();
$mailman->updateList($mitglieder_email_array, "stavos");
?>