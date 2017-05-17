<?php

namespace OpenSkos2\Custom;

use \Exception;
use OpenSkos2\ConfigOptions;

class EPICHandleProxy
{

    private static $theInstance = null;
    private static $host;
    private static $username;
    private static $password;
    private static $prefix;
    private static $guidPrefix;
    private static $resolver;
    private static $forwardLocationPrefix;

    public static function getInstance()
    {
        if (!isset(static::$theInstance)) {
            static::$theInstance = new static;
        }
        return static::$theInstance;
    }

    public static function enabled()
    {
        $ini_array = parse_ini_file('/app/' . ConfigOptions::BACKEND . '/application/configs/application.ini');
        try {
            return isset($ini_array["epic.host"]);
        } catch (Exception $ex) {
            trigger_error("Exception while accessing config members for EPIC handle server.", $ex->getTraceAsString());
        }
    }

    protected function __construct()
    {
        $ini_array = parse_ini_file('/app/' . ConfigOptions::BACKEND . '/application/configs/application.ini');

        try {
            self::$host = $ini_array["epic.host"];
            self::$username = $ini_array["epic.username"];
            self::$password = $ini_array["epic.password"];
            self::$prefix = $ini_array["epic.prefix"];
            self::$guidPrefix = $ini_array["epic.guid.prefix"];
            self::$resolver = $ini_array["epic.resolver"];
            self::$forwardLocationPrefix = $ini_array["epic.forwardLocationPrefix"];
        } catch (Exception $ex) {
            trigger_error("Exception while accessing config members for EPIC handle server.", $ex->getTraceAsString());
        }

        if (!isset(self::$host) or ! isset(self::$username) or ! isset(self::$password)
            or ! isset(self::$prefix) or ! isset(self::$guidPrefix) or ! isset(self::$resolver)) {
            trigger_error(
                "EPIC configuration could not be (fully) read. host= " .
                self::$host . " ,username= " . self::$username . " ,password= " .
                self::$password . " ,prefix= " . self::$prefix . " ,guidPrefix= " .
                self::$guidPrefix . " ,resolver= " .
                self::$resolver . " ,forwardLocationPrefix= " . self::$forwardLocationPrefix,
                E_USER_ERROR
            );
        }
    }

    public function getHost()
    {
        return self::$host;
    }

    public function getUserName()
    {
        return self::$username;
    }

    public function getPassword()
    {
        return self::$password;
    }

    public function getPrefix()
    {
        return self::$prefix;
    }

    public function getGuidPrefix()
    {
        return self::$guidPrefix;
    }

    public function getResolver()
    {
        return self::$resolver;
    }

    public function getForwardLocationPrefix()
    {
        return self::$forwardLocationPrefix;
    }

    public function spiceUUID($c, $uuid)
    {
        $identifierBehindPrefix = ($c === "Concept") ? "C_" : "";
        return self::$guidPrefix . $identifierBehindPrefix . $uuid;
    }

    public function getPID($uuid)
    {
        return self::$resolver . self::$prefix . "/" . $uuid;
    }

    /**
     * @throws Exception when handle could not be resolved
     * @param unknown $PID for example "05C3DB56-5692-11E3-AF8F-1C6F65A666B5"
     */
    public function resolveHandle($PID)
    {
        $PIDSERVICE_URL = self::$host . self::$prefix;
        $PIDSERVICE_USER = self::$username;
        $PIDSERVICE_PASSWD = self::$password;
        $GETPIDURL = $PIDSERVICE_URL . "/" . $PID;


        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt($curl, CURLOPT_URL, $GETPIDURL);
        // Set the authentication options
        curl_setopt($curl, CURLOPT_USERPWD, $PIDSERVICE_USER . ":" . $PIDSERVICE_PASSWD);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Download the given URL, and return output
        $output = curl_exec($curl);
        $info = curl_getinfo($curl);
// 		if( $info['http_code']==200) echo "PID DATA";
// 		if( $info['http_code']==401) echo "Authorization problem";
// 		if( $info['http_code']==404) echo "Not found";
        // Close the cURL resource, and free system resources
        curl_close($curl);
        if ($info['http_code'] != 200) {
            throw new Exception("unexpected result from handle server,"
                . "server returned HTTP code :" . $info['http_code']);
        }
        return $output;
    }

    /**
     * @throws Exception when handle could not be created
     * @param unknown $location for example "www.myserver.org/myfile"
     * @return the guid the handle was created for
     */
    // to do: find usages!!!, e.g. ConceptController, creation of a concept
    // config has credetntials for epic server, see application.ini ... test
    public function createNewHandle($location)
    {
        $PIDSERVICE_URL = self::$host . self::$prefix;
        $PIDSERVICE_USER = self::$username;
        $PIDSERVICE_PASSWD = self::$password;
        $UUID = self::gen_uuid(); //a function to generate a uuid
        $URL_TO_OPEN = $PIDSERVICE_URL . "/" . $UUID;
        echo($URL_TO_OPEN . "\n");
        $data = array(array('type' => 'URL', 'parsed_data' => $location));
        $update_json = json_encode($data);

        // Get cURL resource
        $ch = curl_init();

        //Set the headers to complete the request
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json', 'Content-Type: application/json',
            'Content-Length: ' . strlen($update_json)));

        //set the PUT Action
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

        //SET the postfield data
        curl_setopt($ch, CURLOPT_POSTFIELDS, $update_json);

        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //Set the url with the new name of the PID
        curl_setopt($ch, CURLOPT_URL, $URL_TO_OPEN);

        // Set the authentication options
        curl_setopt($ch, CURLOPT_USERPWD, $PIDSERVICE_USER . ":" . $PIDSERVICE_PASSWD);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        // Download the given URL, and return output
// 		if( $info['http_code']==200) echo "HANDLE EXISTS";
// 		if( $info['http_code']==201) echo "PID CREATED";
// 		if( $info['http_code']==204) echo "PID UPDATED";
// 		if( $info['http_code']==404) echo "HANDLE DOESNT EXIST";

        curl_close($ch);
        if ($info['http_code'] != 201) {
            throw new Exception(
                "unexpected result from handle server, server returned HTTP code :" .
                $info['http_code']
            );
        }
        return $UUID;
    }

    /**
     * @throws Exception when handle could not be created
     * @param unknown $GUID the guid for the handle
     * @param unknown $location for example "www.myserver.org/myfile"
     */
    public function createNewHandleWithGUID($location, $GUID)
    {
        $PIDSERVICE_URL = self::$host . self::$prefix;
        $PIDSERVICE_USER = self::$username;
        $PIDSERVICE_PASSWD = self::$password;
        $UUID = $GUID;
        $URL_TO_OPEN = $PIDSERVICE_URL . "/" . $UUID;
//		echo($URL_TO_OPEN . "\n");
        $data = array(array('type' => 'URL', 'parsed_data' => $location));
        $update_json = json_encode($data);

        // Get cURL resource
        $ch = curl_init();

        //Set the headers to complete the request
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json', 'Content-Type: application/json',
            'Content-Length: ' . strlen($update_json)));

        //set the PUT Action
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

        //SET the postfield data
        curl_setopt($ch, CURLOPT_POSTFIELDS, $update_json);

        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //Set the url with the new name of the PID
        curl_setopt($ch, CURLOPT_URL, $URL_TO_OPEN);

        // Set the authentication options
        curl_setopt($ch, CURLOPT_USERPWD, $PIDSERVICE_USER . ":" . $PIDSERVICE_PASSWD);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);
        if ($info['http_code'] != 201) {
            echo("Url: " . $URL_TO_OPEN . "\n");
            echo("Username: " . $PIDSERVICE_USER . "\n");
            echo("Data: " . $update_json . "\n");
            echo("WW: " . $PIDSERVICE_PASSWD . "\n");
            echo("Error while creating PID, HTTP code returned was: " . $info['http_code'] . "\n");
            echo("Curl error info: " . curl_error($ch) . "\n");
            echo("uuid: " . $UUID . "\n");
            echo("location: " . $location . "\n");
            ob_flush();
            throw new Exception(
                "unexpected result from handle server, server returned HTTP code :" .
                $info['http_code']
            );
        }
    }

    private function removeHandle($handleName)
    {
        $PIDSERVICE_URL = self::$host . self::$prefix;
        $PIDSERVICE_USER = self::$username;
        $PIDSERVICE_PASSWD = self::$password;
        $HANDLENAME = $handleName;
        $PIDTODELETE = $PIDSERVICE_URL . "/" . $HANDLENAME;
        // Get cURL resource
        $curl = curl_init();

        // Set the url to authenticate
        curl_setopt($curl, CURLOPT_URL, $PIDTODELETE);
        // Set the authentication options
        curl_setopt($curl, CURLOPT_USERPWD, $PIDSERVICE_USER . ":" . $PIDSERVICE_PASSWD);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        //set the DELETE action
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        // Include header in result? (0 = yes, 1 = no)
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // Should cURL return or print out the data? (true = return, false = print)
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        // Download the given URL, and return output
        $output = curl_exec($curl);
        $info = curl_getinfo($curl);
// 		if( $info['http_code']==204) echo "The PID was successfully deleted";
// 		if( $info['http_code']==401) echo "Authorization failed: Either your login or your password is wrong.";
// 		if( $info['http_code']==403) echo "HTTP/1.1 403 Forbidden: The operation is not permitted.";
// 		if( $info['http_code']==405) echo "HTTP/1.1 405 Method Not Allowed: The submitted url with PID is wrong";
        // Close the cURL resource, and free system resources
        curl_close($curl);
        if ($info['http_code'] != 204) {
            throw new Exception("unexpected result from handle server, "
                . "server returned HTTP code :" . $info['http_code']);
        }
    }

    private function genUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
