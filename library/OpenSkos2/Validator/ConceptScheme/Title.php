<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
use OpenSkos2\Validator\GenericProperties\Title as GenericTitle;

class Title extends AbstractConceptSchemeValidator
{
    protected function validateSchema(ConceptScheme $resource)
    {
       $this->errorMessages = GenericTitle::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
