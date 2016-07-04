<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Validator\GenericProperties\Publisher as GenericPublisher;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class Publisher extends AbstractSetValidator
{
    
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = GenericPublisher::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
