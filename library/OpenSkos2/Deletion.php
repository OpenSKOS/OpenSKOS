<?php

namespace OpenSkos2;

use OpenSkos2\ConfigOptions;

class Deletion implements \OpenSkos2\Interfaces\Deletion
{

    private $resourceManager;
    private $customDeletion;

    public function __construct($manager)
    {
        if (ConfigOptions::DEFAULT_DELETION) {
            $this->resourceManager = $manager;
        } else {
            $this->customDeletion = new \OpenSkos2\Custom\Deletion($manager);
        }
    }

    public function canBeDeleted($uri)
    {
        if (ConfigOptions::DEFAULT_DELETION) {
            $query = 'SELECT (COUNT(?s) AS ?COUNT) WHERE {?s ?p <' . $uri . '> . } LIMIT 1';
            $references = $this->resourceManager->query($query);
            return (($references[0]->COUNT->getValue()) < 1);
        } else {
            return $this->customDeletion->canBeDeleted($uri);
        }
    }
}
