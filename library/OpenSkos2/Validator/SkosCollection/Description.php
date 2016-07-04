<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;
use OpenSkos2\Validator\GenericProperties\Description as GenericDescription;

class Description extends AbstractSkosCollectionValidator
{
   protected function validateSkosCollection(SkosCollection $resource)
    {
       $this->errorMessages = GenericDescription::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
