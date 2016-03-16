<?php

namespace OpenSkos2\Validator\SkosCollection;

use OpenSkos2\Rdf\Resource as RdfResource;
use OpenSkos2\Validator\CommonProperties;

class Title extends AbstractSkosCollectionValidator
{
    protected function validateSkosCollection(RdfResource $resource)
    {
        return CommonProperties\Title::validate($resource, $this->getErrorMessages());
    }
}
