<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
use OpenSkos2\Namespaces\Skos;

class HasTopConcept extends AbstractConceptSchemeValidator
{

    protected function validateSchema(ConceptScheme $resource)
    {
        if ($this->conceptReferenceCheckOn) {
            return $this->validateProperty($resource, Skos::HASTOPCONCEPT, false, false, false, false, Skos::CONCEPT);
        } else {
            return true;
        }
    }
}
