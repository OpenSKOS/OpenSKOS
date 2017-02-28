<?php

namespace OpenSkos2\MyInstitutionModules;

use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\SkosCollection;
use OpenSkos2\RelationType;

class Deletion {
    
    private $resourceManager;
    
    public function __construct($manager) {
        $this->resourceManager = $manager;
    }
    
    public function canBeDeleted($uri) {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                return $this->conceptCanBeDeleted($uri);
            case ConceptScheme::TYPE:
                return $this->conceptSchemeCanBeDeleted($uri);
            case Set::TYPE:
                return $this->setCanBeDeleted($uri);
            case Tenant::TYPE:
                return $this->tenantCanBeDeleted($uri);  
            case SkosCollection::TYPE:
                return $this->skosCollectionCanBeDeleted($uri);
            case RelationType::TYPE:
                return $this->relationCanBeDeleted($uri);
            default:
                return false;
        }
    }

    
    private function canBeDeletedBasic($uri) {
        $query = 'SELECT (COUNT(?s) AS ?COUNT) WHERE {?s ?p <' . $uri . '> . } LIMIT 1';
        $references = $this->resourceManager->query($query);
        return (($references[0]->COUNT->getValue()) < 1);
    }

    private function conceptCanBeDeleted($uri) {
        // the lowerst-level resource, 
        // can be always deleted if authorisation rights allow (checked in another place)
        // but with taking crae that the corresponding relations are cleaned
        return true;
    }

    private function conceptSchemeCanBeDeleted($uri) {
        return $this->canBeDeletedBasic($uri);
    }

    private function tenantCanBeDeleted($uri) {
        return $this->canBeDeletedBasic($uri);
    }

    private function setCanBeDeleted($uri) {
        return $this->canBeDeletedBasic($uri);
    }

    private function skosCollectionCanBeDeleted($uri) {
        return $this->canBeDeletedBasic($uri);
    }

    private function relationCanBeDeleted($uri) {
        return $this->canBeDeletedBasic($uri);
    }

}   

