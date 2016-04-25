<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OpenSkos2\Namespaces;

class NamespaceAdmin
{
    static private $namespaces = [Dc::NAME_SPACE, DcTerms::NAME_SPACE, Dcmi::NAME_SPACE, Foaf::NAME_SPACE, 
        OpenSkos::NAME_SPACE, Org::NAME_SPACE, Owl::NAME_SPACE, Rdf::NAME_SPACE, Rdfs::NAME_SPACE, 
        Skos::NAME_SPACE, SkosXl::NAME_SPACE, Xsd::NAME_SPACE, vCard::NAME_SPACE];
    
    public static function getStandardNamespaces() {
        return self::$namespaces;
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
        $retVal = in_array($prefix, self::$namespaces);
        return $retVal;
    }

}