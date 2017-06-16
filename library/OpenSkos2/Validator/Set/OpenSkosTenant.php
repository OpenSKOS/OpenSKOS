<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenSkosTenant extends AbstractSetValidator
{

    protected function validateSet(Set $resource)
    {
         return $this->checkTenant($resource);
    }

}
