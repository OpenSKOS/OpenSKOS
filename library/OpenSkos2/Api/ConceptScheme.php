<?php

namespace OpenSkos2\Api;

class ConceptScheme extends AbstractTripleStoreResource
{

    public function __construct(
        \OpenSkos2\ConceptSchemeManager $manager,
        \OpenSkos2\PersonManager $personManager
    ) {
    
        $this->manager = $manager;
        $this->customInit = $this->manager->getCustomInitArray();
        $this->deletionIntegrityCheck = new \OpenSkos2\IntegrityCheck($manager);
        $this->personManager = $personManager;
        $this->limit = $this->customInit['limit'];
    }
}
