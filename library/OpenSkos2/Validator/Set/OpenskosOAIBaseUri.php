<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Validator\GenericProperties\OAIBaseUri;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosOAIBaseUri extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = OAIBaseUri::validate($resource);
       return (count($this->errorMessages) === 0);
       
    }
}
