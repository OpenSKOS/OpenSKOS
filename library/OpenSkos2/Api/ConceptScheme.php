<?php

namespace OpenSkos2\Api;

use OpenSkos2\ConceptSchemeManager;
use OpenSkos2\Authorisation;
use OpenSkos2\Deletion;

class ConceptScheme extends AbstractTripleStoreResource
{

    public function __construct(ConceptSchemeManager $manager)
    {
        $this->manager = $manager;
        $this->authorisation = new Authorisation($manager);
        $this->deletion = new Deletion($manager);
    }
}
