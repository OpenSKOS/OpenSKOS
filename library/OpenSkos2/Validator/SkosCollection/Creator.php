<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;
use OpenSkos2\Validator\GenericProperties\Creator as GenericCreator;

class Creator extends AbstractSkosCollectionValidator
{
  
    protected function validateSkosCollection(SkosCollection $resource)
    {
       $this->errorMessages = GenericCreator::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
