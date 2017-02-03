<?php

namespace OpenSkos2\Validator\Relation;

use OpenSkos2\Relation;
use OpenSkos2\Validator\AbstractRelationValidator;

class Title extends AbstractRelationValidator {

    protected function validateRelation(Relation $resource) {
      return $this->validateTitle($resource);
    }

}
