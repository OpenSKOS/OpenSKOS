<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
use OpenSkos2\Validator\GenericProperties\HasTopConcept as GenericHasTopConcept;

class HasTopConcept extends AbstractConceptSchemeValidator
{
  
    protected function validateSchema(ConceptScheme $resource)
    {
       $this->errorMessages = GenericHasTopConcept::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
