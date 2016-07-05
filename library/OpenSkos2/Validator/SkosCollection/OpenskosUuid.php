<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;

class OpenskosUuid extends AbstractSkosCollectionValidator
{
    protected function validateSkosCollection(SkosCollection $resource)
    {
       return $this->validateUUID($resource);
    }
}
