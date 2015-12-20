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
$mailman = new Mailman_class();

echo("Start Auth\n");
$nami->auth();

// Iteriere über Einstellungen
foreach ($config["mailliste_mapping"] as $key => $value) {
  if(gettype($value) !== "array") {
      throw new RuntimeException("config[mailliste_mapping] muss ein array sein");
  }

  $listname = $value[0];
  $nami_id = $value[1];
  $leiter = $value[2];

  $mitglieder_email_array = $nami->listMitgliederEmailArray($leiter, $nami_id);
  $mailman->updateList($mitglieder_email_array, $listname);

}
echo("Done\n");

?>
