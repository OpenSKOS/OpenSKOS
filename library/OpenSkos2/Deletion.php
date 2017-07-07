<?php

namespace OpenSkos2;

class Deletion implements \OpenSkos2\Interfaces\Deletion
{

    private $resourceManager;
    private $customDeletion;
    private $defaultOn;

    public function __construct($manager)
    {
        $init = $manager->getInitArray();
        $this->defaultOn = $init["custom"]["default_deletion"];
        if ($this->defaultOn) {
            $this->resourceManager = $manager;
        } else {
            $this->customDeletion = new \OpenSkos2\Custom\Deletion($manager);
        }
    }

    public function canBeDeleted($uri)
    {
        if ($this->defaultOn) {
            if ($this->resourceManager->getResourceType() !== \OpenSkos2\Concept::TYPE) {
                $query = 'SELECT (COUNT(?s) AS ?COUNT) WHERE {?s ?p <' . $uri . '> . } LIMIT 1';
                $references = $this->resourceManager->query($query);
                if (($references[0]->COUNT->getValue()) >0) {
                    throw new \Exception('The resource cannot be deleted because there are other resources '
                        . 'referring to it within this storage.');
                }
            }
        } else {
            $this->customDeletion->canBeDeleted($uri);
        }
    }
}
