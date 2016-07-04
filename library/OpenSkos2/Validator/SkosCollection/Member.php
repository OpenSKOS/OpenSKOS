<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;
use OpenSkos2\Validator\GenericProperties\Member as GenericMember;

class Member extends AbstractSkosCollectionValidator
{
  
    protected function validateSkosCollection(SkosCollection $resource)
    {
       $this->errorMessages = GenericMember::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
