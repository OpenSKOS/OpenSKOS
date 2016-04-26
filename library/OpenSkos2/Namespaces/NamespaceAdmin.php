<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Namespaces;
use EasyRdf\RdfNamespace;

class NamespaceAdmin
{
   
    public static function getStandardNamespaces() {
        return array_values(RdfNamespace::namespaces());
    }
    
    public static function getNamespacePrefixCandidate($str) {
         $border = strrpos($str, "#");
         if (!$border) {
             $border = strrpos($str, "/");
         }
         if (!$border) {
             throw new \OpenSkos2\Api\Exception("The string ". $str . " does not contain # or / and cannot be considered as a property name with its namespace uri. ");
         }
         $retVal = substr($str, 0, $border+1);
         return $retVal;
    }
    
    public static function isPropertyFromStandardNamespace($property) {
        $prefix = self :: getNamespacePrefixCandidate($property);
        $retVal = in_array($prefix, self::getStandardNamespaces());
        return $retVal;
    }

}