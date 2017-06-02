<?php

namespace OpenSkos2\Custom;

use OpenSkos2\Concept;
use OpenSkos2\ConceptScheme;
use OpenSkos2\Set;
use OpenSkos2\Tenant;
use OpenSkos2\SkosCollection;
use OpenSkos2\RelationType;
use OpenSkos2\Api\Exception\InvalidArgumentException;

class Deletion implements \OpenSkos2\Interfaces\Deletion
{

    private $resourceManager;

    public function __construct($manager)
    {
        $this->resourceManager = $manager;
    }

    public function canBeDeleted($uri)
    {
        $type = $this->resourceManager->getResourceType();
        switch ($type) {
            case Concept::TYPE:
                $this->conceptCanBeDeleted($uri);
                break;
            case ConceptScheme::TYPE:
                $this->conceptSchemeCanBeDeleted($uri);
                break;
            case Set::TYPE:
                $this->setCanBeDeleted($uri);
                break;
            case Tenant::TYPE:
                $this->tenantCanBeDeleted($uri);
                break;
            case SkosCollection::TYPE:
                $this->skosCollectionCanBeDeleted($uri);
                break;
            case RelationType::TYPE:
                $this->relationCanBeDeleted($uri);
                break;
            default:
                $this->canBeDeletedBAsic($uri);
        }
    }

    private function canBeDeletedBasic($uri)
    {
         if ($this->resourceManager->getResourceType() !== \OpenSkos2\Concept::TYPE) {
            $query = 'SELECT (COUNT(?s) AS ?COUNT) WHERE {?s ?p <' . $uri . '> . } LIMIT 1';
            $references = $this->resourceManager->query($query);
            if (($references[0]->COUNT->getValue()) > 0) {
                throw new InvalidArgumentException('The resource cannot be deleted because there are other resources referring to it within this storage', 401);
            }
        }
    }

    private function conceptCanBeDeleted($uri)
    {
        // the lowerst-level resource,
        // can be always deleted if authorisation rights allow (checked in another place)
    }

    private function conceptSchemeCanBeDeleted($uri)
    {
        $this->canBeDeletedBasic($uri);
    }

    private function tenantCanBeDeleted($uri)
    {
        $this->canBeDeletedBasic($uri);
    }

    private function setCanBeDeleted($uri)
    {
        $this->canBeDeletedBasic($uri);
    }

    private function skosCollectionCanBeDeleted($uri)
    {
        $this->canBeDeletedBasic($uri);
    }

    private function relationCanBeDeleted($uri)
    {
        $this->canBeDeletedBasic($uri);
    }

}
