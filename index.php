<?php
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