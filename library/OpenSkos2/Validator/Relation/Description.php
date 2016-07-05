<?php

namespace OpenSkos2\Validator\UserRelation;

use OpenSkos2\UserRelation;
use OpenSkos2\Validator\AbstractRelationValidator;

class Description extends AbstractRelationValidator
{
   protected function validateRelation(UserRelation $resource)
    {
       return $this->validateDescription($resource);
    }
}
