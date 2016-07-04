<?php

namespace OpenSkos2\Validator\UserRelation;

use OpenSkos2\UserRelation;
use OpenSkos2\Validator\AbstractRelationValidator;
use OpenSkos2\Validator\GenericProperties\Description as GenericDescription;

class Description extends AbstractRelationValidator
{
   protected function validateRelation(UserRelation $resource)
    {
       $this->errorMessages = GenericDescription::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
