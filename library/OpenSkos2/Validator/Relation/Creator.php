<?php

namespace OpenSkos2\Validator\Relation;

use OpenSkos2\Relation;
use OpenSkos2\Validator\AbstractRelationValidator;
use OpenSkos2\Validator\GenericProperties\Creator as GenericCreator;

class Creator extends AbstractRelationValidator
{
  
    protected function validateRelation(Relation $resource)
    {
       $this->errorMessages = GenericCreator::validate($resource);
       return (count($this->errorMessages) === 0);
    }
}
