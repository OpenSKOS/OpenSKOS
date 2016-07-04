<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Validator\GenericProperties\OpenskosCode as GenericOpenskosCode;

class OpenskosCode extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       $this->errorMessages = GenericOpenskosCode::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
