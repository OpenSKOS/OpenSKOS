<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Validator\GenericProperties\ConceptBaseUri;

class OpenskosConceptBaseUri extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = ConceptBaseUri::validate($resource);
       return (count($this->errorMessages) === 0);
       
    }
}
