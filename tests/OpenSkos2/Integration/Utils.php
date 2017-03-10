<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Tests\OpenSkos2\Integration;

class Utils {
  
  public static function getAbout($response){
        $dom = new \Zend_Dom_Query();
        $dom->setDocumentXML($response->getBody());
        $description = $dom->queryXpath('/rdf:RDF/rdf:Description'); 
        if ($description->count()<1) {
          throw Exception("rdf:Description element is not declared");
        }
        $resURI = $description->current()->getAttribute("rdf:about");
        if ($resURI === "") {
          throw Exception("No valid uri for SKOS concept");
        }
        return $resURI;
    }
  
}