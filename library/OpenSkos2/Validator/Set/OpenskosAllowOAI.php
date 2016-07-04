<?php

namespace OpenSkos2\Validator\Set;


use OpenSkos2\Validator\GenericProperties\AllowOAI;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosAllowOAI extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = AllowOAI::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
