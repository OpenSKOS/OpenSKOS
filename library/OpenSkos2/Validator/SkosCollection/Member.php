<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;
use OpenSkos2\Namespaces\Skos;

class Member extends AbstractSkosCollectionValidator
{
  
    protected function validateSkosCollection(SkosCollection $resource)
    {
       return $this->validateProperty($resource, Skos::MEMBER, false, false, false, false, Skos::CONCEPT);
    }
}
