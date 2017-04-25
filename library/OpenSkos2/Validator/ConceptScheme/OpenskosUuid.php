<?php

namespace OpenSkos2\Validator\ConceptScheme;

use OpenSkos2\ConceptScheme;
use OpenSkos2\Validator\AbstractConceptSchemeValidator;

class OpenskosUuid extends AbstractConceptSchemeValidator
{

    protected function validateSchema(ConceptScheme $resource)
    {
        return $this->validateUUID($resource);
    }
}
