<?php

namespace OpenSkos2\Validator\UserRelation;

use OpenSkos2\UserRelation;
use OpenSkos2\Validator\AbstractRelationValidator;

class Title extends AbstractRelationValidator {

    protected function validateRelation(UserRelation $resource) {
       return $this->validateTitle($resource);
    }

}
