<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
use OpenSkos2\Validator\GenericProperties\Description as GenericDescription;

class Description extends AbstractConceptSchemeValidator
{
   protected function validateSchema(ConceptScheme $resource)
    {
       $this->errorMessages = GenericDescription::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
