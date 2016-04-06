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
include_once 'classes/MailmanList_class.php';

print("NBAS - NAMI Bezirks API Sammlung\n");
print("Lade config.php\n");
if(file_exists('config.php')) {
    include 'config.php';
}
else {
    throw new Exception("Achtung! Es muss eine config.php existieren. Ihr könnt euch die config.php.template kopieren und anpassen.", 1);
}

$nami = new NamiConnection_class($config);

print("Starte NAMI auth() ...");
$nami->auth();
print(" ok\n");

// Iteriere über Einstellungen
foreach ($config["mailliste_mapping"] as $key => $value) { // Für jeden Eintrag
  print("Lade Maillingliste");
  if(gettype($value) !== "array") {
      throw new RuntimeException("config[mailliste_mapping] muss ein array sein");
  }
  // Lese Einstellungen für aktuellen Eintrag ein
  $listname = $value[0];
  $nami_id = $value[1];
  $leiter = $value[2];
  print(" " . $listname . "\n");

  // NAMI Anfrage starten
  print("Lade Daten aus NAMI ... ");
  $mitglieder_email_array = $nami->listMitgliederEmailArray($leiter, $nami_id);
  print("ok\n");

  // Daten an Mailman übergeben
  print("Aktualisiere Maillingliste ... ");
  $mailman = new MailmanList_class($listname, $config["tmpdir"], $config["skipssl"]);
  $mailman->replace($mitglieder_email_array);

  // Wenn es zusätzliche Emails gibt, dann füge diese zu Mailman hinzu
  if(file_exists($config["mailliste_additional_dir"] . "/" . $listname)) {
    print("\nLade zusätzliche Adressen ... ");
    $mailman->import($config["mailliste_additional_dir"] . "/" . $listname);
    print("ok\n");
  }
  $mailman->update();
  print("ok\n");
  print("\n");
}
print("Done\n");

?>
