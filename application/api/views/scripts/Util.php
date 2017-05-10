<?php

class Util
{

    public static function switchFromHtmlToOtherFormat($newFormat, $uri, $callBack=null)
    {
        if (self::endsWith($uri, '.html')) {
            $result = str_replace('.html', ".$newFormat", $uri);
            if ($newFormat==='jsonp') {
                $result = $result."?callback=$callBack";
            }
            return $result;
        } else {
            $result= $uri."?format=$newFormat";
             if ($newFormat==='jsonp') {
                $result = $result."&callback=$callBack";
            } 
            return $result;
        }
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

}
