<?php

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Validator\AbstractConceptValidator;

class OpenSkosTenant extends AbstractConceptValidator
{

    protected function validateConcept(Concept $resource)
    {
         return $this->checkTenant($resource);
    }
}
