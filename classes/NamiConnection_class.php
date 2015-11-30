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

include_once 'HttpWrapper_class.php';
include_once 'NamiMitglied_class.php';

// State-Konstanten
define("IDLE", 0); /// Warten auf Verbindungsaufbau
define("AUTH", 1); /// Authentifizierung hat angefangen
define("CON", 2); /// Verbindung steht
define("ERR", 99); /// Fehler

/** Die NamiConnection Klasse macht die ganze Kommunikation und Logik mit dem NAMI REST Server */
class NamiConnection_class {
    var $connection_status =  IDLE; /// State-Machine intern
    protected $nami_user = "1111111"; /// Nami API Benutzer
    protected $nami_password = "PASSWORD"; /// Nami API PAsswort
    protected $nami_server = "https://namitest.dpsg.de"; /// URL wo NAMI gefunden wird

    protected $auth_url = "/ica/rest/nami/auth/manual/sessionStartup"; /// URL unter der man sich authentifizieren kann
    protected $search_url = "/ica/rest/api/1/2/service/nami/search/result-list"; /// URL für Suchanfragen

    protected static $search_json_str = '{
                            "taetigkeitId": "6",
                            "mglStatusId": "AKTIV",
                            "mglTypeId": "MITGLIED",
                            "untergliederungId": 3
                        }'; /// Lesbare Beschreibung der Suchanfrage JSON Objekte
    protected $search_json = NULL; /// Objekt des $search_json_str

    public $current_message = ""; /// Meldung, die dem aufrufenden Programm signalisiert wird.

    protected $HttpWrapper=NULL; /// Interner HTTP Handler


    public static $FIELD_LEITER = 6; //taetigkeitId
    public static $FIELD_WOE = 1; //untergliederungId
    public static $FIELD_JUPFI = 2; //untergliederungId
    public static $FIELD_PFADI = 3; //untergliederungId
    public static $FIELD_ROVER = 4; //untergliederungId

    /** Constructor,
        Initialisiert HttpWrapper und ergänzt relative Urls zu absoluten Urls, damit Intern gearbeitet werden kann.
        @param config Array mit drei Einträgen: config[apiuser_mitgliedsnummer], config[apiuser_password], config[namiserver]
    */
    function NamiConnection_class($config) {
        $this->HttpWrapper = new HttpWrapper_class();
        $this->auth_url = $this->nami_server . $this->auth_url;
        $this->search_url = $this->nami_server . $this->search_url;
        $this->search_json = json_decode(NamiConnection_class::$search_json_str);

        if(gettype($config) !== "array") {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Parameter ist kein Array";
            throw new RuntimeException($this->current_message);
        }

        if(gettype($config["apiuser_mitgliedsnummer"]) !== "string") {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: config[\"apiuser_mitgliedsnummer\"] muss ein String sein";
            throw new RuntimeException($this->current_message);
        }

        if(gettype($config["apiuser_password"]) !== "string") {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: config[\"apiuser_password\"] muss ein String sein";
            throw new RuntimeException($this->current_message);
        }

        if(gettype($config["namiserver"]) !== "string") {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: config[\"namiserver\"] muss ein String sein";
            throw new RuntimeException($this->current_message);
        }

        $this->nami_user = $config["apiuser_mitgliedsnummer"];
        $this->nami_password = $config["apiuser_password"];

        $url = parse_url($this->nami_server);
        if($url["scheme"] !== "https") {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: config[\"namiserver\"] muss mit https:// starten, NAMI leitet nicht https um, das wird hier nicht berücksichtigt.";
            throw new RuntimeException($this->current_message);
        }
        $this->nami_server = $url["scheme"] . "://" . $url["host"];

        if($this->search_json == NULL and json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException(HttpWrapper_class::$_json_messages[json_last_error()]);
        }
    }

    /** Funktion die Authentifizierung ausführt
        @return true oder Exception
    */
    function auth() {
        if($this->connection_status > IDLE and $this->connection_status < ERR) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: kann nicht auth() aufrufen, wenn bereits eine Verbindung steht.";
            throw new RuntimeException($this->current_message);
        }

        $this->connection_status = AUTH;

        // Baue Login Parameter zusammen
        $authparams = array(
            "username" => $this->nami_user,
            "password" => $this->nami_password,
            "Login" => "API"
        );

        $auth_response = $this->request("POST", array("url" => $this->auth_url, "method" => "POST", "params" => $authparams), true);

        // Wenn kein Fehler vorliegt, geh daran die Headers zu untersuchen
        $auth_headers = $auth_response->headers;

        if(!isset($auth_headers["Location"])) { // Prüfe ob Location da ist und nicht NULL
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Fehler beim Anfragen der Session, der HTTP Header enthält kein \"Location\"-Feld";
            throw new RuntimeException($this->current_message);
        }

        $do_auth_url = $auth_headers["Location"];
        $this->connection_status = CON;

        // Hier fragen wir die Weiterleitung an und bekommen (hoffentlich) das Cookie
        $login_response = $this->request("GET", array("url" => $do_auth_url));

        if(!isset($login_response->cookies["JSESSIONID"])) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Fehler beim Login, keine passenden Cookies empfangen";
            throw new RuntimeException($this->current_message);
        }

        return true;
    }

    /** Erzeuht eine Liste der Mitglieder
        @param leiter Boolean Wert, true wenn Leiter
        @param stufe Integer Wert, kann anhand $FIELD_XXX die stufe ausgewählt werden
        @return Array mit NamiMitglied_class Objekten*/
    function listMitglieder($leiter, $stufe) {
        $stufe = intval($stufe);
        $leiter = boolval($leiter);

        if($stufe == 0) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Ungültige Stufe " . $stufe;
            throw new RuntimeException($this->current_message);
        }

        if($this->connection_status != CON) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Authentifizierung noch nicht erfolgt";
            throw new RuntimeException($this->current_message);
        }

        // Setze Suchwerte in JSON Suchobjekt
        $this->search_json->untergliederungId = $stufe;
        if($leiter) {
            $this->search_json->taetigkeitId = NamiConnection_class::$FIELD_LEITER;
        } else {
            $this->search_json->taetigkeitId = "";
        }

        // Bauə Parameter für Anfrage zusammen
        $list_params = array(
            "searchedValues" => $this->search_json,
            "page" => 1,
            "start" => 0,
            "limit" => 999999
        );

        // Führe HTTP Anfrage aus
        $list_response = $this->request("GET", array("url" => $this->search_url, "params" => $list_params));

        $list_mitglieder = array();
        if(!isset($list_response->json)) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Kein JSON Objekt in Antwort";
            throw new RuntimeException($this->current_message);
        }

        if(!isset($list_response->json->response->data)) {
            print_r($list_response->json);
            $this->connection_status = "ERR";
            $lstr = "";
            foreach ($list_response->headers as $key => $value) {
                $lstr = $lstr . $key . ": " . $value . "\n";
            }
            $this->current_message = "Fehler: Kein JSON Data Objekt in Antwort: \n" . $list_response->body . "\n". $lstr . "\n";
            throw new RuntimeException($this->current_message);
        }

        // Erzeuge Rückgabe-Array
        foreach ($list_response->json->response->data as $key => $value) {
           if(!isset($value->entries)) {
                $this->connection_status = "ERR";
                $this->current_message = "Fehler: Kein JSON Entries Objekt in Antwort";
                throw new RuntimeException($this->current_message);
            }
           array_push($list_mitglieder, new NamiMitglied_class($value->entries));
        }
        return $list_mitglieder;
    }

    /** Erzeuht eine Liste der Mitglieder
        @param leiter Boolean Wert, true wenn Leiter
        @param stufe Integer Wert, kann anhand $FIELD_XXX die stufe ausgewählt werden
        @return Array mit Email-Strings*/
    function listMitgliederEmailArray($leiter, $stufe) {
        $mitglieder = $this->listMitglieder($leiter, $stufe);
        $mitglieder_email_array = array();
        foreach ($mitglieder as $key => $value) {
            echo($value . "\n");
            array_push($mitglieder_email_array, $value->fields["entries"]["email"]);
        }
        return $mitglieder_email_array;
    }

    /** Interne Funktion, die die ganzen HTTP POST Anfragen erledigt und Fehlerchecks macht
        @param type Wenn POST, dann Post request, ansonsten GET request
        @param data Array mit "url" Feld und optionalen "param" Feld
        @return HttpResponse_class Objekt
    */
    function request($type, $data, $skip_cookies=false) {
        $type_value = gettype($type);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        $type_value = gettype($data);
        if($type_value !== "array") {
            throw new RuntimeException("2. Argument muss Array sein " . $type_value);
        }

        $type_value = gettype($skip_cookies);
        if($type_value !== "boolean") {
            throw new RuntimeException("3. Argument muss Bool sein " . $type_value);
        }

        $response = $this->HttpWrapper->request($type, $data, $skip_cookies);

        // Prüfe auf HTTP Fehler
        if(!$response->success) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler:" + $response->errorMessage();
            throw new RuntimeException($this->current_message);
        }

        // @TODO: Die Rückgabe ist nicht ganz klar, aber hier sollre auch auf das JSON Feld statusCode == 0 geprüft werden
        if($response->body != "" and !isset($response->json->success) and !isset($response->json->statusCode)) {
            $this->connection_status = "ERR";
            $this->current_message = "Fehler, kein statusCode/success in Antwort definiert " . $response->body;
            throw new RuntimeException($this->current_message);
        }

        // Prüfe HTTP Status Code
        if($this->connection_status == AUTH and $response->httpcode != 302) { // Auth muss mit 302 antworten
            $this->connection_status = "ERR";
            $this->current_message = "Fehler: Fehler beim Anfragen der Session, der HTTP Code ist nicht 302: " . $response->httpcode . " -- " . $this->connection_status;
            throw new RuntimeException($this->current_message);
        } elseif($this->connection_status != AUTH and $response->httpcode != 307 and $response->httpcode >= 300 or $response->httpcode < 200) { // Nur 2XX Status Codes erlauben
            $connection_status = "ERR";
            $this->current_message = "Fehler: Fehler beim Anfragen der Session, der HTTP Code ist nicht 2XX: " . $response->httpcode . " -- " . $this->connection_status;
            throw new RuntimeException($this->current_message);
        } elseif($this->connection_status != AUTH and $response->httpcode == 307) { // Nur 2XX Status Codes erlauben
            $connection_status = "ERR";
            $this->current_message = "Fehler: Fehler beim Anfragen der Session, der HTTP Code ist nicht 2XX: " . $response->httpcode . " -- " . $this->connection_status . "\n";
            $this->current_message = $this->current_message . "Fehler: 307 zeigt eigentlich ein API nicht verfügbar an";
            throw new RuntimeException($this->current_message);
        }

        // Bereite nicht gesetzte Werte vor
        if(!isset($response->json)) {
            $response->json = new stdClass();
        }
        if(!isset($response->json->success)) {
            $response->json->success = NULL;
        }

        if(!isset($response->json->statusCode)) {
            $response->json->statusCode = NULL;
        }

        // Prüfe Rückmeldung von NAMI
        if($response->body != "" and ($response->json->success) !== true and ($response->json->statusCode) !== 0) {
            $this->connection_status = "ERR";
            $errmsg = "Kein JSON statusMessage in Antwort";
            if(isset($response->json->statusMessage)) {
                $errmsg = $response->json->statusMessage;
            }
            $this->current_message = "Fehler, Success ist: " . $response->json->success . " " . $errmsg . " " . $response->body;
            throw new RuntimeException($this->current_message);
        }
        // Prüfe auf API Fehlermeldung
        if(isset($response->json) and isset($response->json->response->responseType) and $response->json->response->responseType == "ERROR") {
            $this->connection_status = "ERR";
            $var_info = print_r($response->json,true);
            $this->current_message = "Fehler, API signalisiert Funktionsfehler:\n" . $var_info . "\n";
            throw new RuntimeException($this->current_message);
        }
        return $response;
    }
}
?>