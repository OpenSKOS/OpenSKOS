<?php

namespace OpenSkos2;

class Logging
{
    
    public static function varLogger($message, $object, $fileName)
    {
        ob_start(); // start buffer capture
        //var_dump($object);
        $contents = ob_get_contents();
        ob_end_clean();
        error_log($message . $contents, 3, $fileName);
    }
    
    public static function failureMessaging($response, $action)
    {
        $result = "\n Failed to " . $action . ", response header: " . $response->getHeader('X-Error-Msg') .
                "\n Failed to " . $action . ", response message: " . $response->getMessage();// .
                //"\n Failed to " . $action . ", responce body: " . $response->getBody();
        print $result;
        return $result;
    }
}
