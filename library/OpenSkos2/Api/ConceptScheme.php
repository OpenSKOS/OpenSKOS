<?php

namespace OpenSkos2\Api;

use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\MyInstitutionModules\Authorisation;
use OpenSkos2\MyInstitutionModules\Deletion;

class ConceptScheme extends AbstractTripleStoreResource
{

    public function __construct(ConceptSchemeManager $manager)
    {
        $this->manager = $manager;
        $this->authorisationManager = new Authorisation($manager);
        $this->deletionManager = new Deletion($manager);
    }
}
