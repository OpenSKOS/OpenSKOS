<?php

namespace OpenSkos2\Validator\Set;

use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Set;

class Title extends AbstractSetValidator
{
    protected function validateSet(Set $resource)
    {
       return parent::genericValidate('\CommonProperties\Title::validate', $resource);
       
    }
}