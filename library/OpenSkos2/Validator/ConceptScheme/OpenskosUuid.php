<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;
use OpenSkos2\Validator\GenericProperties\Uuid;

class OpenskosUuid extends AbstractConceptSchemeValidator
{
    protected function validateSchema(ConceptScheme $resource)
    {
       $this->errorMessages = Uuid::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
