<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosUuid extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       return $this->validateUUID($resource);
    }
}
