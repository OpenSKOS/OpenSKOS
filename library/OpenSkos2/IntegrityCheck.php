<?php

namespace OpenSkos2;

class IntegrityCheck
{

    private $init;

    public function __construct($manager)
    {
        $this->init = $manager->getInitArray();
    }

    public function canBeDeleted($uri)
    {
        $integrity_check_on  = $this->init["options"]["delete"]["integrity_check"];
        if ($integrity_check_on === "true") {
            if ($this->resourceManager->getResourceType() !== \OpenSkos2\Concept::TYPE) {
                $query = 'SELECT (COUNT(?s) AS ?COUNT) WHERE {?s ?p <' . $uri . '> . } LIMIT 1';
                $references = $this->resourceManager->query($query);
                if (($references[0]->COUNT->getValue()) > 0) {
                    throw new \Exception('The resource cannot be deleted because there are other resources '
                    . 'referring to it within this storage.');
                }
            }
        } else {
            return true;
        }
    }
}
