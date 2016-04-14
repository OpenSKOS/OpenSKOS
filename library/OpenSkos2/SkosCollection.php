<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace OpenSkos2;

use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Rdf\Literal;

class SkosCollection extends Resource
{
    const TYPE = Skos::SKOSCOLLECTION;

    public function __construct($uri = null) {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
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
    
   public function getDescription()
    {
        if ($this->hasProperty(DcTerms::DESCRIPTION)) {
            return (string)$this->getPropertySingleValue(DcTerms::DESCRIPTION);
        } else {
            return null;
        }
    }

   public function addMetadata($user, $params, $oldParams) {
        $metadata = [];

        if (count($oldParams) === 0) { // a completely new resource under creation
            $userUri = $user->getFoafPerson()->getUri();
            $nowLiteral = function () {
                return new Literal(date('c'), null, Literal::TYPE_DATETIME);
            };

            $metadata = [
                DcTerms::CREATOR => new Uri($userUri),
                DcTerms::DATESUBMITTED => $nowLiteral(),
            ];
        } else {
            $metadata = [
                OpenSkos::UUID => new Literal($oldParams['uuid']),
                DcTerms::CREATOR => new Uri($oldParams['creator']),
                DcTerms::DATESUBMITTED => new Literal($oldParams['dateSubmitted'], null, Literal::TYPE_DATETIME),
            ];
        }
        foreach ($metadata as $property => $defaultValue) {
            $this->setProperty($property, $defaultValue);
        }
    }
}