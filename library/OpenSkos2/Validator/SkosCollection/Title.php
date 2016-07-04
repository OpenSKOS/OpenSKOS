<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;
use OpenSkos2\Validator\GenericProperties\Title as GenericTitle;

class Title extends AbstractSkosCollectionValidator {

    protected function validateSkosCollection(SkosCollection $resource) {
        $this->errorMessages = GenericTitle::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }

}
