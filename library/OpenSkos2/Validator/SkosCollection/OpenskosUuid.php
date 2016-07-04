<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\SkosCollection;
use OpenSkos2\Validator\AbstractSkosCollectionValidator;
use OpenSkos2\Validator\GenericProperties\Uuid;

class OpenskosUuid extends AbstractSkosCollectionValidator
{
    protected function validateSkosCollection(SkosCollection $resource)
    {
       $this->errorMessages = Uuid::validate($resource, $this->forUpdate);
       return (count($this->errorMessages) === 0);
    }
}
