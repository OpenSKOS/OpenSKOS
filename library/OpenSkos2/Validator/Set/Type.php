<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Set;
use OpenSkos2\Validator\GenericProperties\Type as GenericType;

class Type extends AbstractSetValidator
{
    
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = GenericType::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
