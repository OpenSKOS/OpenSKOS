<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Namespaces\OpenSkos;

class OpenskosConceptBaseUri extends AbstractSetValidator
{

    protected function validateSet(Set $resource)
    {
        return $this->validateProperty($resource, OpenSkos::CONCEPTBASEURI, false, true, false, false);
    }
}
