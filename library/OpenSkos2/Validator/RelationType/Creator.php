<?php

namespace OpenSkos2\Validator\Relation;

use OpenSkos2\RelationType;
use OpenSkos2\Validator\AbstractRelationTypeValidator;

class Creator extends AbstractRelationTypeValidator
{
  
    protected function validateRelation(RelationType $resource)
    {
       return $this->validateCreator($resource);
    }
}
