<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
use OpenSkos2\Validator\GenericProperties\Creator as GenericCreator;

class Creator extends AbstractConceptSchemeValidator
{
  
    protected function validateSchema(ConceptScheme $resource)
    {
       $this->errorMessages = GenericCreator::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
