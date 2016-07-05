<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosOAIBaseUri extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       return $this->validateProperty($resource, OpenSkos::OAI_BASEURL, false, true, true, false, true, false);
       
    }
}
