<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;

class Title extends AbstractSkosCollectionValidator {

    protected function validateSkosCollection(SkosCollection $resource) {
        return parent::genericValidate('\CommonProperties\Title::validate', $resource);
    }

}
