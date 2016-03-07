<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


namespace OpenSkos2;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Rdfs;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Tenant;
use OpenSKOS_Db_Table_Row_Tenant;
use OpenSKOS_Db_Table_Tenants;
use Rhumsaa\Uuid\Uuid;
use \OpenSkos2\Rdf\ResourceManager;
use OpenSkos2\Exception\UriGenerationException;

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
    
    public function getComment()
    {
        if ($this->hasProperty(Rdfs::COMMENT)) {
            return (string)$this->getPropertySingleValue(Rdfs::COMMENT);
        } else {
            return null;
        }
    }

   
   
   public function ensureMetadata(Uri $person) {

        $forFirstTimeInOpenSkos = [
            DcTerms::CREATOR => $person
        ];

        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }
    }

    public function selfGenerateUri($tenantcode, \OpenSkos2\SkosCollectionManager $manager) {
        if (!$this->isBlankNode()) {
            throw new UriGenerationException(
            'The skos collection already has an uri. Can not generate a new one.'
            );
        }

        $uri = $this->assembleUri($tenantcode);

        if ($manager->askForUri($uri, true)) {
            throw new UriGenerationException(
            'The generated uri "' . $uri . '" is already in use for skos collections.'
            );
        }

        $this->setUri($uri);
        return $uri;
    }

    // how to geberate uri for the skos:collection
    protected function assembleUri($tenantcode) {
        $uri = $tenantcode . ':' . Uuid::uuid4();
        return $uri;
    }

}