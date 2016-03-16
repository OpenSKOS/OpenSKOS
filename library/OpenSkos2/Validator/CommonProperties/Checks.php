<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace OpenSkos2\Validator\CommonProperties;

class Checks {

    public static function checkBoolean($val, $type, &$retVal) {
        $testVal = trim($val);
        if (!($testVal === "true" || $testVal === "false")) {
            $retVal[] = 'The value of ' . $type . ' must be set to true or false. ';
        }
    }

}
