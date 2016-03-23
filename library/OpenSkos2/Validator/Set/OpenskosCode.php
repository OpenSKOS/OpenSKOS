<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class OpenskosCode extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       return parent::genericValidate('\CommonProperties\OpenskosCode::validate', $resource);
       
    }
}
