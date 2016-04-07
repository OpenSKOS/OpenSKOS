<?php

namespace OpenSkos2\Validator\Sest;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;

class Description extends AbstractSetValidator
{
   protected function validateSet(Set $resource)
    {
        return parent::genericValidate('\CommonProperties\Description::validate', $resource);
    }
}
