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
include_once 'CurlWrapper_class.php';

/** Eine Klasse, welche sich um CURL auf einer höheren Ebene stülpt, damit das ganze "bequem" ist */
class HttpWrapper_class {
    protected $CurlWrapper = NULL; // Der Low-Level CURL Wrapper

    public static $_json_messages = array(
        JSON_ERROR_NONE => 'No error has occurred',
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
        JSON_ERROR_SYNTAX => 'Syntax error',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
    );


    function HttpWrapper_class() {
        if(!is_callable('json_encode')){
                throw new RuntimeException("JSON PHP modul ist nicht verfügbar");
        }
        $this->CurlWrapper = new CurlWrapper_class();
    }

   /** Generic Request Function with error checking
       @param type "GET" oder "POST"
       @param values array mit url, parameter usw
       @param skip_cookies Wenn true, werden keine Cookies an Server gesendet
   */
   function request($type, $values, $skip_cookies) {
        $params = array();
        if(isset($values["params"])) {
            $params = $values["params"];
        }
        $curl_params = array();
        if(isset($params)) {
            foreach ($params as $field => $data) {
                if (is_object($data)) {
                    $json_fielddata = json_encode($data);
                    $curl_params[urlencode($field)] =  urlencode($json_fielddata);
                } else {
                    $curl_params[urlencode($field)] =  urlencode($data);
                }
            }
        }
        $type_value = gettype($type);
        if($type_value !== "string") {
            throw new RuntimeException("1. Argument muss String sein " . $type_value);
        }

        $type_value = gettype($curl_params);
        if($type_value !== "array") {
            throw new RuntimeException("2. Argument muss Array sein " . $type_value);
        }

        $type_value = gettype($skip_cookies);
        if($type_value !== "boolean") {
            throw new RuntimeException("3. Argument muss Bool sein " . $type_value);
        }

        $this->CurlWrapper->request($type, $values["url"], $curl_params, $skip_cookies);

        $current_response = new HttpResponse_class();
        $current_response->body = $this->CurlWrapper->body;
        $current_response->httpcode = $this->CurlWrapper->httpcode;
        $current_response->json = json_decode($this->CurlWrapper->body);
        $current_response->headers = $this->CurlWrapper->headers;
        $current_response->cookies = $this->CurlWrapper->cookies;
        if($current_response->body != "" and $current_response->json == NULL and json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(static::$_json_messages[json_last_error()] . "\n" . $current_response->body);
        }
        $current_response->success = true;

        return  $current_response;
    }
}

/** Klasse beschreibt eine HTTP Aantwort und speichert auch die Zustände ab*/
class HttpResponse_class {
    var $cookies=NULL; // Cookie Array
    var $success = false;
    var $headers=NULL; // Header array
    var $httpcode=NULL; // HTTP Response Code
    var $body=NULL; // String mit den Daten
    var $json=NULL; //JSON Daten Objekt
}
?>