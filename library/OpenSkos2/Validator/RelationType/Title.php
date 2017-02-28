<?php

namespace OpenSkos2\Validator\Relation;

use OpenSkos2\RelationType;
use OpenSkos2\Validator\AbstractRelationTypeValidator;
use OpenSkos2\Namespaces\DcTerms;

class Title extends AbstractRelationTypeValidator {

    protected function validateRelation(RelationType $resource) {
        return $this->validateProperty($resource, DcTerms::TITLE, true, false, false, true);
    }

}
