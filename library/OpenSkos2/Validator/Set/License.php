<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Set;
use OpenSkos2\Namespaces\DcTerms;

class License extends AbstractSetValidator
{

    protected function validateSet(Set $resource)
    {
        return $this->validateProperty($resource, DcTerms::LICENSE, true, false, false, false);
    }
}
