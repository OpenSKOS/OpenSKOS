<?php

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Validator\AbstractConceptValidator;

class OpenSkosSet extends AbstractConceptValidator
{

    protected function validateConcept(Concept $resource)
    {
         return $this->checkSet($resource);
    }
}
