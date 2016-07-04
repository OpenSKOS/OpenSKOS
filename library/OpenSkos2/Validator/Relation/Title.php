<?php

namespace OpenSkos2\Validator\UserRelation;

use OpenSkos2\UserRelation;
use OpenSkos2\Validator\AbstractRelationValidator;
use OpenSkos2\Validator\GenericProperties\Title as GenericTitle;

class Title extends AbstractRelationValidator {

    protected function validateRelation(UserRelation $resource) {
        $this->errorMessages = GenericTitle::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }

}
