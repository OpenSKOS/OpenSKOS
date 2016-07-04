<?php

namespace OpenSkos2\Validator\Sest;

use OpenSkos2\Set;
use OpenSkos2\Validator\AbstractSetValidator;
use OpenSkos2\Validator\GenericProperties\Description as GenericDescription;

class Description extends AbstractSetValidator
{
   protected function validateSet(Set $resource)
    {
       $this->errorMessages = GenericDescription::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
