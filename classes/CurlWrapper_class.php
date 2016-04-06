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

/** Wrapper Klasse, die das Interface zu CURL herstellt*/
class CurlWrapper_class {
        const COOKIE_FILE = "nami_curl_cookies.txt";

        public $body = ""; /// Rohe HTTP (nicht HTTP!) Antwort Body
        public $headers = array(); /// Array mit Header Infos
        public $httpcode = 0; /// HTTP Return Code
        public $cookies = array(); /// Cookie Array
        protected $ch = NULL; /// Curl Handler
        protected $TMP_PATH = "."; // Pfad für temporäre Daten und Cookies
        protected $COOKIE_FILE = ""; // Name des Cookies File, inkl. Pfad (!)
        protected $SKIPSSL = false; // Wenn true, wird SSL Verbindungszertifikate nicht überprüft.
                                   // Das ist ein bescheuerter Workaraound, aber Debian kommt zur Zeit nicht
                                   // mit dem NAMI Zertifikat zurecht. (openssl bug)

        /** Interne Funktion, die ein Header String in ein Array verwandelt.
        @param $header_text Header STring
        @return Array mit key=>value Struktur*/
        protected function headersToArray($header_text) {
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if ($i === 0)
                    $headers['http_code'] = $line;
                else {
                    list ($key, $value) = array_pad(explode(': ', $line, 2), 2, NULL);
                    $headers[$key] = $value;
                }
            }
            return $headers;
        }

        /** Konstruktor.
        @param tmppath Pfad zu temporären Daten wie Cookies, darf nicht mit / enden!
        @param skipssl Gefährliche Einstellung. Ändere es nur auf true, wenn du Ahnung davon hast!
        */
        function CurlWrapper_class($tmppath = ".", $skipssl = false) {
            $this->TMP_PATH = $tmppath;
            $this->COOKIE_FILE = $tmppath . "/" . self::COOKIE_FILE;
            $this->SKIPSSL = $skipssl;

            if(!is_callable('curl_init')){
                throw new RuntimeException("CURL PHP modul ist nicht verfügbar");
            }
            if(!is_writable($this->TMP_PATH)) {
                throw new RuntimeException("\nKann im TMP Verzeichnis keine Datei anlegen\nFür die cookies muss das Programm eine Datei " . $this->COOKIE_FILE . " anlegen und schreiben können.\n");
            }

            if(file_exists($this->COOKIE_FILE) && !is_writable($this->COOKIE_FILE)) {
                throw new RuntimeException("\nFür die cookies muss das Programm eine Datei " . $this->COOKIE_FILE . " anlegen und schreiben können.\n");
            }
        }

        /** Generische Request Funktion, damit es nicht so viel doppelter Code für GET und POST Requests gibt
            @param type: GET oder POST
            @param url: url
            @param params: array mit Parameter
            @param skip_cookies: Wenn true, werden keine Cookies an Server gesendet.
            @return response
        */
        function request($type, $url, $params, $skip_cookies) {
            $type_value = gettype($type);
            if($type_value !== "string") {
                throw new RuntimeException("1. Argument muss String sein " . $type_value);
            }

            $type_value = gettype($url);
            if($type_value !== "string") {
                throw new RuntimeException("2. Argument muss String sein " . $type_value);
            }

            $type_value = gettype($params);
            if($type_value !== "array") {
                throw new RuntimeException("3. Argument muss Array sein " . $type_value);
            }

            $type_value = gettype($skip_cookies);
            if($type_value !== "boolean") {
                throw new RuntimeException("4. Argument muss Bool sein " . $type_value);
            }

            if($type == "POST") {
                $this->preparePOST($url, $params);
            } else {
                $this->prepareGET($url, $params);
            }

            curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->COOKIE_FILE);
            if(!$skip_cookies) {
                curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->COOKIE_FILE);
            }
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($this->ch, CURLOPT_HEADER, true);

            // Gefährlich! Mach das nur, wenn du Ahnung davon hast
            if($this->SKIPSSL === TRUE) {
              curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            }

            $output = curl_exec($this->ch);
            $errNo = curl_errno($this->ch);
            if ($errNo) {
                throw new RuntimeException($errNo . "\n" . curl_error($this->ch));
            }

            $info = curl_getinfo($this->ch);
            curl_close($this->ch);

            $this->httpcode = $info["http_code"];
            $this->headers = $this->headersToArray(substr($output, 0, $info['header_size']));
            $this->body = substr($output, $info['header_size']);

            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $output, $matches);
            $cookies = array();
            foreach($matches[1] as $item) {
                parse_str($item, $cookie);
                $cookies = array_merge($cookies, $cookie);
            }

            $c = array_merge($this->cookies, $cookies);

            $this->cookies = $c;

            return $this->body;
        }

        /**  Macht die GET spezifischen Einstellungen
            @param url: url
            @param params: array mit Parameter
            @return -
        */
        protected function prepareGET($url, $params) {
            $data_param = "";
            $first = true;
            foreach ($params as $field => $data) {
                if ($first) {
                    $first = false;
                    $data_param = "?";
                } else {
                    $data_param .="&";
                }
                $data_param = $data_param . "$field=$data";
            }

            $url = $url . $data_param;

            $this->ch = curl_init($url);
        }

        /** Macht die Post spezifischen Einstellungen
            @param params: array mit Parameter
            @return -
        */
        protected function preparePOST($url, $params) {
            $this->ch = curl_init($url);
            curl_setopt($this->ch, CURLOPT_POST, 1);

            $data_param = "";
            $first = true;
            foreach ($params as $field => $data) {
                if ($first) {
                    $first = false;
                } else {
                    $data_param .="&";
                }
                $data_param = $data_param . "$field=$data";
            }
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data_param);
        }
}
?>
