<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosWebPage extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       return $this->validateProperty($resource, OpenSkos::WEBPAGE, false, true, false, true);
     }
}
