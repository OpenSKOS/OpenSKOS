<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace OpenSkos2;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdfs;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Resource;

class Schema extends Resource
{
    const TYPE = Skos::CONCEPTSCHEME;

    public function __construct($uri = null) {
        parent::__construct($uri);
        //$this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }
    
    // how to add get property-reference attribute??
    
    public function getTitle()
    {
        if ($this->hasProperty(DcTerms::TITLE)) {
            return (string)$this->getPropertySingleValue(DcTerms::TITLE);
        } else {
            return null;
        }
        
    }
    
    
    public function getCreator()
    {
        if ($this->hasProperty(DcTerms::CREATOR)) {
            return (string)$this->getPropertySingleValue(DcTerms::CREATOR);
        } else {
            return null;
        }
    }
    
    public function getComment()
    {
        if ($this->hasProperty(Rdfs::COMMENT)) {
            return (string)$this->getPropertySingleValue(Rdfs::COMMENT);
        } else {
            return null;
        }
    }

   
   
   public function addMetadata($map) {

        $forFirstTimeInOpenSkos = [
            DcTerms::CREATOR => $map['user'],
        ];

        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }
    }

    
}