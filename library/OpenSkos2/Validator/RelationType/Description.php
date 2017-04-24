<?php

namespace OpenSkos2\Validator\RelationType;

use OpenSkos2\RelationType;
use OpenSkos2\Validator\AbstractRelationTypeValidator;

class Description extends AbstractRelationTypeValidator
{
   protected function validateRelation(RelationType $resource)
    {
       return $this->validateDescription($resource);
    }
}
