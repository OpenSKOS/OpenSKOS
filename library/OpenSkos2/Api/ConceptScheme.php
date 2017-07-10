<?php

namespace OpenSkos2\Api;

class ConceptScheme extends AbstractTripleStoreResource
{

    public function __construct(
        \OpenSkos2\ConceptSchemeManager $manager,
        \OpenSkos2\PersonManager $personManager
    ) {
    
        $this->manager = $manager;
        $this->init = $this->manager->getInitArray();
        $this->deletion_integrity_check = new \OpenSkos2\IntegrityCheck($manager);
        $this->personManager = $personManager;
    }
}
